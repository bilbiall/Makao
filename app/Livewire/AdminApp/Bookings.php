<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Booking;
use App\Models\House;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Bookings extends Component
{
    use WithPagination;
    use ExportsCsv;

    public string $houseFilter = '';

    public string $statusFilter = '';

    public function updatingHouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirm(int $id): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::MANAGE_BOOKINGS), 403);

        $booking = StaffScope::onHouseOrAssignedHouse(Booking::query())->findOrFail($id);
        $booking->update(['status' => 'confirmed', 'expires_at' => null]);
    }

    public function check_in(int $id): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::MANAGE_BOOKINGS), 403);

        $booking = StaffScope::onHouseOrAssignedHouse(Booking::query())->findOrFail($id);
        $booking->update(['status' => 'checked_in']);
    }

    public function check_out(int $id): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::MANAGE_BOOKINGS), 403);

        $booking = StaffScope::onHouseOrAssignedHouse(Booking::query())->findOrFail($id);
        $booking->update(['status' => 'checked_out']);
    }

    public function cancel(int $id): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::MANAGE_BOOKINGS), 403);

        $booking = StaffScope::onHouseOrAssignedHouse(Booking::query())->findOrFail($id);
        $booking->update(['status' => 'cancelled', 'expires_at' => null]);
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onHouseOrAssignedHouse(Booking::query())
            ->with('house')
            ->latest();

        if ($this->houseFilter) {
            $query->where('house_id', $this->houseFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query;
    }

    public function export()
    {
        $bookings = $this->filteredQuery()->get();

        return $this->streamCsv(
            'bookings.csv',
            ['House', 'Guest Name', 'Guest Phone', 'Check In', 'Check Out', 'Nights', 'Total Amount', 'Status', 'Payment Status'],
            $bookings->map(fn (Booking $booking) => [
                $booking->house?->house_name,
                $booking->guest_name,
                $booking->guest_phone,
                optional($booking->check_in)->format('Y-m-d'),
                optional($booking->check_out)->format('Y-m-d'),
                $booking->nights,
                $booking->total_amount,
                $booking->status,
                $booking->payment_status,
            ])
        );
    }

    public function render()
    {
        $bookings = $this->filteredQuery()->paginate(10);

        // House::query() has a direct location_id column (not a house() relation),
        // so this mirrors StaffScope::onHouseOrAssignedHouse()'s branching rather
        // than reusing it directly - that helper assumes the model being queried
        // has a house() relation, which House itself doesn't.
        $housesQuery = House::query();
        if (StaffScope::isScopedStaff()) {
            $housesQuery->whereIn('location_id', StaffScope::locationIds());
        } elseif (StaffScope::isAgent()) {
            $housesQuery->whereIn('id', StaffScope::houseIds());
        }
        $houses = $housesQuery->orderBy('house_name')->get();

        return view('livewire.admin-app.bookings', [
            'bookings' => $bookings,
            'houses' => $houses,
            'canManageBookings' => Auth::user()->hasPermission(StaffPermissions::MANAGE_BOOKINGS),
        ])->layout('components.layouts.app', ['title' => 'Bookings']);
    }
}
