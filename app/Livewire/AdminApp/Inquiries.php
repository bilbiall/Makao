<?php

namespace App\Livewire\AdminApp;

use App\Models\HouseInquiry;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manages the lightweight "ask a question" leads from stays/show.blade.php's
 * inquiry form - scoped the same way as Promotions (agent: their assigned
 * houses only; admin/landlord: everything).
 */
class Inquiries extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(StaffScope::isAgent() || in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    protected function baseQuery()
    {
        $query = HouseInquiry::with('house')->latest();

        if (StaffScope::isAgent()) {
            $query->whereIn('house_id', StaffScope::houseIds());
        }

        return $query;
    }

    public function markResponded(int $inquiryId): void
    {
        $this->baseQuery()->findOrFail($inquiryId)->update(['status' => 'responded']);
    }

    public function render()
    {
        return view('livewire.admin-app.inquiries', [
            'inquiries' => $this->baseQuery()->paginate(15),
        ])->layout('components.layouts.app', ['title' => 'Inquiries']);
    }
}
