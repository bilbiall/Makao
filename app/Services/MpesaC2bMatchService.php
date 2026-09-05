<?php

namespace App\Services;

use App\Models\MpesaC2bTransaction;
use App\Models\MpesaChannel;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Turns one inbound Safaricom C2B confirmation into a matched (and credited) or
 * needs-review MpesaC2bTransaction row. Only two signals ever auto-credit - an
 * exact account-code match or an exact phone match - both scoped to the channel's
 * own candidate tenants (a specific property's tenants if the channel is
 * property-specific, otherwise the whole landlord's). Amount is only ever used as
 * a hint in match_reason for a human to read, never to decide a match by itself.
 */
class MpesaC2bMatchService
{
    public function process(MpesaChannel $channel, array $payload): MpesaC2bTransaction
    {
        $transId = $payload['TransID'] ?? null;

        $existing = $transId ? MpesaC2bTransaction::where('trans_id', $transId)->first() : null;
        if ($existing) {
            return $existing;
        }

        $billRefNumber = trim((string) ($payload['BillRefNumber'] ?? ''));
        $msisdn = $this->normalizePhone((string) ($payload['MSISDN'] ?? ''));
        $amount = (float) ($payload['TransAmount'] ?? 0);

        $candidates = $this->candidateTenants($channel);

        $tenant = $this->matchByAccountCode($candidates, $billRefNumber);
        $matchStatus = $tenant ? 'matched_by_account' : null;

        if (!$tenant) {
            $tenant = $this->matchByPhone($candidates, $msisdn);
            $matchStatus = $tenant ? 'matched_by_phone' : null;
        }

        $reason = $this->buildReason($candidates, $billRefNumber, $msisdn, $amount, $tenant, $matchStatus);

        $transaction = MpesaC2bTransaction::create([
            'mpesa_channel_id' => $channel->id,
            'landlord_id' => $channel->landlord_id,
            'location_id' => $channel->location_id,
            'tenant_id' => $tenant?->id,
            'house_id' => $tenant?->house_id,
            'trans_id' => $transId,
            'trans_time' => $this->parseTransTime($payload['TransTime'] ?? null),
            'trans_amount' => $amount,
            'business_shortcode' => $payload['BusinessShortCode'] ?? $channel->business_shortcode,
            'bill_ref_number' => $billRefNumber ?: null,
            'msisdn' => $msisdn ?: null,
            'payer_name' => trim(implode(' ', array_filter([
                $payload['FirstName'] ?? null,
                $payload['MiddleName'] ?? null,
                $payload['LastName'] ?? null,
            ]))) ?: null,
            'match_status' => $matchStatus ?? 'needs_review',
            'match_reason' => $reason,
            'raw_payload' => $payload,
        ]);

        if ($tenant) {
            $this->creditTenant($transaction, $tenant, $amount, $transId);
        }

        if ($transaction->match_status === 'needs_review') {
            $this->notifyAdmins($transaction);
        }

        return $transaction;
    }

    /** Staff manually assigning a "needs_review" row to a tenant from the C2B Payments
     *  dashboard - runs the exact same crediting step process() would have run on an
     *  automatic match, just triggered by a human instead of the account/phone match. */
    public function manuallyMatch(MpesaC2bTransaction $transaction, Tenant $tenant): void
    {
        $transaction->update([
            'tenant_id' => $tenant->id,
            'house_id' => $tenant->house_id,
            'match_status' => 'manually_matched',
            'match_reason' => trim(($transaction->match_reason ?? '') . " Manually assigned to {$tenant->tenant_name} by staff."),
        ]);

        $this->creditTenant($transaction->fresh(), $tenant, (float) $transaction->trans_amount, $transaction->trans_id);
    }

    protected function candidateTenants(MpesaChannel $channel): Collection
    {
        $query = Tenant::withoutGlobalScopes()->where('landlord_id', $channel->landlord_id);

        if ($channel->location_id) {
            $query->whereHas('house', fn ($q) => $q->where('location_id', $channel->location_id));
        }

        return $query->get();
    }

    protected function matchByAccountCode(Collection $candidates, string $billRefNumber): ?Tenant
    {
        if ($billRefNumber === '') {
            return null;
        }

        $matches = $candidates->filter(
            fn (Tenant $t) => $t->payment_account_code && strcasecmp(trim($t->payment_account_code), $billRefNumber) === 0
        );

        return $matches->count() === 1 ? $matches->first() : null;
    }

    protected function matchByPhone(Collection $candidates, string $msisdn): ?Tenant
    {
        if ($msisdn === '') {
            return null;
        }

        $matches = $candidates->filter(
            fn (Tenant $t) => $t->phone_number && $this->normalizePhone($t->phone_number) === $msisdn
        );

        return $matches->count() === 1 ? $matches->first() : null;
    }

    protected function buildReason(Collection $candidates, string $billRefNumber, string $msisdn, float $amount, ?Tenant $tenant, ?string $matchStatus): string
    {
        if ($tenant && $matchStatus === 'matched_by_account') {
            return "Account number \"{$billRefNumber}\" matched {$tenant->tenant_name}'s assigned code.";
        }

        if ($tenant && $matchStatus === 'matched_by_phone') {
            return "Phone number {$msisdn} matched {$tenant->tenant_name} on file (account number \"{$billRefNumber}\" didn't match anyone).";
        }

        $parts = [];
        $parts[] = $billRefNumber !== ''
            ? "Account number \"{$billRefNumber}\" didn't match any tenant's assigned code."
            : 'No account number was entered.';
        $parts[] = $msisdn !== ''
            ? "Phone {$msisdn} isn't on file for any candidate tenant."
            : 'No phone number was available to match.';

        if ($amount > 0) {
            $amountMatches = $candidates->filter(function (Tenant $t) use ($amount) {
                $invoice = $t->invoices()->whereIn('status', ['unpaid', 'partial'])->latest()->first();
                return $invoice && (float) $invoice->balance === $amount;
            });

            if ($amountMatches->count() === 1) {
                $only = $amountMatches->first();
                $parts[] = "Amount KES " . number_format($amount) . " matches only {$only->tenant_name}'s outstanding balance - worth checking by hand, but not auto-applied.";
            } elseif ($amountMatches->count() > 1) {
                $parts[] = 'Amount KES ' . number_format($amount) . " matches {$amountMatches->count()} possible tenants - needs manual review.";
            }
        }

        $parts[] = 'Needs manual review.';

        return implode(' ', $parts);
    }

    protected function creditTenant(MpesaC2bTransaction $transaction, Tenant $tenant, float $amount, ?string $transId): void
    {
        $invoice = $tenant->invoices()->whereIn('status', ['unpaid', 'partial'])->latest()->first();

        if (!$invoice) {
            $transaction->update([
                'match_status' => 'needs_review',
                'match_reason' => $transaction->match_reason . " Matched to {$tenant->tenant_name} but they have no open invoice - possible prepayment or overpayment, needs a manual decision.",
            ]);
            $this->notifyAdmins($transaction);
            return;
        }

        $existingPayment = $transId ? Payment::where('transaction_id', $transId)->first() : null;

        $payment = $existingPayment ?: Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'amount_paid' => $amount,
            'payment_method' => 'mpesa_c2b',
            'payment_reference' => $transId,
            'transaction_id' => $transId,
            'status' => 'completed',
            'payment_date' => now(),
            'payment_type' => 'mpesa_c2b',
        ]);

        $transaction->update([
            'invoice_id' => $invoice->id,
            'payment_id' => $payment?->id,
        ]);
    }

    protected function notifyAdmins(MpesaC2bTransaction $transaction): void
    {
        try {
            $admins = User::where('role', 'admin')->where('landlord_id', $transaction->landlord_id)->get();

            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\DatabaseNotification(
                    'C2B payment needs review',
                    "A Paybill payment of KES " . number_format((float) $transaction->trans_amount) . " couldn't be automatically matched to a tenant. {$transaction->match_reason}",
                    null
                ));
            }
        } catch (\Throwable $e) {
            // ignore notification failures - the transaction row itself is the durable record
        }
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    protected function parseTransTime(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        try {
            // Safaricom sends TransTime as YYYYMMDDHHmmss.
            return \Carbon\Carbon::createFromFormat('YmdHis', $raw)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
