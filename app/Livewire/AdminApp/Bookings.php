<?php

namespace App\Livewire\AdminApp;

use App\Models\Booking;
use App\Support\StaffScope;
use Livewire\Component;
use Livewire\WithPagination;

class Bookings extends Component
{
    use WithPagination;

    public function confirm(int $id): void
    {
        $booking = StaffScope::onHouseOrAssignedHouse(Booking::query())->findOrFail($id);
        $booking->update(['status' => 'confirmed', 'expires_at' => null]);
    }

    public function cancel(int $id): void
    {
        $booking = StaffScope::onHouseOrAssignedHouse(Booking::query())->findOrFail($id);
        $booking->update(['status' => 'cancelled', 'expires_at' => null]);
    }

    public function render()
    {
        $bookings = StaffScope::onHouseOrAssignedHouse(Booking::query())
            ->with('house')
            ->latest()
            ->paginate(10);

        return view('livewire.admin-app.bookings', ['bookings' => $bookings])
            ->layout('components.layouts.app', ['title' => 'Bookings']);
    }
}
