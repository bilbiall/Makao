<?php

namespace App\Livewire;

use App\Services\ImageCompressor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone_number = '';
    public string $bio = '';
    public $new_avatar = null;
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number ?? '';
        $this->bio = $user->bio ?? '';
    }

    /** Only an agent has a public page today (see routes/web.php's /agents/{user:slug}) - everyone else's bio/photo just isn't shown anywhere yet. */
    public function hasPublicProfile(): bool
    {
        return Auth::user()->role === 'agent';
    }

    public function publicProfileUrl(): ?string
    {
        if (!$this->hasPublicProfile()) {
            return null;
        }

        return route('agents.show', Auth::user()->ensureSlug());
    }

    public function updateDetails(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'new_avatar' => 'nullable|image|max:5120',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'bio' => $this->bio,
        ];

        if ($this->new_avatar) {
            $disk = Storage::disk('public');
            $directory = 'avatars';
            $disk->makeDirectory($directory);

            $relativePath = $directory . '/' . Str::random(24) . '.jpg';
            (new ImageCompressor(maxDimension: 600, quality: 80))->compress($this->new_avatar->getRealPath(), $disk->path($relativePath));

            if ($user->avatar_path) {
                $disk->delete($user->avatar_path);
            }

            $data['avatar_path'] = $relativePath;
        }

        $user->update($data);
        $this->reset('new_avatar');

        session()->flash('profile-updated', 'Your details have been updated.');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('password-updated', 'Your password has been changed.');
    }

    public function render()
    {
        return view('livewire.profile')
            ->layout('components.layouts.app', ['title' => 'Profile']);
    }
}
