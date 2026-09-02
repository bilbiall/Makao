<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

/**
 * A payment attempt against a Booking. Deliberately separate from Payment/Invoice/
 * MpesaTransaction - it never touches rent/tenant-balance accounting. Reconciliation
 * here only ever updates Booking.payment_status/status, nothing else.
 */
class BookingPayment extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'status',
        'checkout_request_id',
        'phone_number',
        'reference',
        'meta',
        'landlord_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (!$payment->landlord_id && $payment->booking_id) {
                $payment->landlord_id = Booking::withoutGlobalScopes()->find($payment->booking_id)?->landlord_id;
            }
        });
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Mark this payment completed and reconcile the booking - the only place
     * Booking.status/payment_status are derived from payment activity.
     */
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);

        $booking = $this->booking;
        $totalPaid = $booking->payments()->where('status', 'completed')->sum('amount');

        $booking->update([
            'payment_status' => $totalPaid >= $booking->total_amount ? 'paid' : 'deposit_paid',
            'status' => 'confirmed',
            'expires_at' => null,
        ]);
    }
}
