<?php

namespace App\Livewire\AdminApp;

use App\Models\Location;
use App\Models\MpesaChannel;
use App\Services\MpesaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * App-shell equivalent of Filament's MpesaChannelResource - lets a landlord give
 * any of their properties its own M-Pesa shortcode/credentials for STK push,
 * without leaving the app-shell for the Filament panel. Same access rule as the
 * Filament resource (MpesaChannelResource::canAccess()): admin/landlord only.
 */
class MpesaChannels extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $label = '';
    public ?int $location_id = null;
    public string $business_shortcode = '';
    public string $consumer_key = '';
    public string $consumer_secret = '';
    public string $passkey = '';
    public bool $sandbox = true;
    public bool $stk_enabled = true;

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    protected function rules(): array
    {
        return [
            'label' => 'nullable|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'business_shortcode' => 'required|string|max:20|unique:mpesa_channels,business_shortcode,' . ($this->editingId ?? 'NULL'),
            'consumer_key' => 'required|string|max:255',
            // Editing keeps the existing secret/passkey when left blank - a landlord
            // shouldn't have to re-paste a secret they already saved just to change
            // the label or shortcode.
            'consumer_secret' => ($this->editingId ? 'nullable' : 'required') . '|string|max:255',
            'passkey' => 'nullable|string|max:255',
            'sandbox' => 'boolean',
            'stk_enabled' => 'boolean',
        ];
    }

    public function startCreate(): void
    {
        $this->cancelForm();
        $this->showForm = true;
    }

    public function startEdit(int $channelId): void
    {
        $channel = MpesaChannel::findOrFail($channelId);

        $this->editingId = $channel->id;
        $this->label = $channel->label ?? '';
        $this->location_id = $channel->location_id;
        $this->business_shortcode = $channel->business_shortcode;
        $this->consumer_key = $channel->consumer_key;
        $this->consumer_secret = '';
        $this->passkey = '';
        $this->sandbox = (bool) $channel->sandbox;
        $this->stk_enabled = (bool) $channel->stk_enabled;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->reset([
            'showForm', 'editingId', 'label', 'location_id', 'business_shortcode',
            'consumer_key', 'consumer_secret', 'passkey',
        ]);
        $this->sandbox = true;
        $this->stk_enabled = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'label' => $this->label ?: null,
            'location_id' => $this->location_id ?: null,
            'business_shortcode' => $this->business_shortcode,
            'consumer_key' => $this->consumer_key,
            'sandbox' => $this->sandbox,
            'stk_enabled' => $this->stk_enabled,
        ];

        if ($this->consumer_secret !== '') {
            $data['consumer_secret'] = $this->consumer_secret;
        }

        if ($this->passkey !== '') {
            $data['passkey'] = $this->passkey;
        }

        if ($this->editingId) {
            MpesaChannel::findOrFail($this->editingId)->update($data);
            session()->flash('mpesa-saved', 'Channel updated.');
        } else {
            MpesaChannel::create($data);
            session()->flash('mpesa-saved', 'Channel created.');
        }

        $this->cancelForm();
    }

    public function delete(int $channelId): void
    {
        MpesaChannel::findOrFail($channelId)->delete();
        session()->flash('mpesa-saved', 'Channel deleted.');
    }

    public function registerC2b(int $channelId, MpesaService $mpesa): void
    {
        $channel = MpesaChannel::findOrFail($channelId);

        abort_unless((bool) Auth::user()->landlord?->c2b_enabled, 403, 'C2B isn\'t enabled for your account yet - contact support to have this turned on.');

        $result = $mpesa->registerC2bUrls($channel);

        if ($result['success']) {
            $channel->update(['c2b_enabled' => true, 'c2b_registered_at' => now()]);
            session()->flash('mpesa-saved', 'C2B registered with Safaricom.');
        } else {
            session()->flash('mpesa-error', 'Registration failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    public function render()
    {
        $channels = MpesaChannel::with('location')->latest()->get();
        $locations = Location::orderBy('location_name')->get();

        return view('livewire.admin-app.mpesa-channels', [
            'channels' => $channels,
            'locations' => $locations,
            'c2bEnabled' => (bool) Auth::user()->landlord?->c2b_enabled,
        ])->layout('components.layouts.app', ['title' => 'M-Pesa Channels']);
    }
}
