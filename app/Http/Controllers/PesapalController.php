<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\PendingPayment;
use App\Services\PesapalService;
//use Illuminate\Http\Request;

class PesapalController extends Controller
{
    /**
     * Initiate a payment (scaffold).
     * This stores the pending payment data in session and shows a simple page
     * where you can simulate Pesapal callback. Replace this logic with
     * real Pesapal API calls (OAuth, STK push, or iframe checkout) as needed.
     */
    public function initiate(Request $request, Invoice $invoice, PesapalService $pesapal)
    {
        $user = Auth::user();
        if (!$user || !$user->tenant || $invoice->tenant_id !== $user->tenant->id) {
            abort(403);
        }

        $amount = $request->query('amount', $invoice->balance);
        // Create a pending payment record
        $pending = PendingPayment::create([
            'tenant_id' => $user->tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'reference' => Str::uuid()->toString(),
            'status' => 'pending',
            'meta' => ['initiated_by' => $user->id],
            'expires_at' => now()->addHours(1),
        ]);

        // Try to create a real Pesapal checkout if credentials exist
        $result = $pesapal->createPayment($pending);
        if ($result['success'] && !empty($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        // Fallback to the simulation view (keeps previous UX if integration isn't configured)
        $settings = Setting::singleton();
        $pesapalConfig = $settings->payload['pesapal'] ?? null;

        return view('pesapal.initiate', [
            'invoice' => $invoice,
            'amount' => $amount,
            'pesapal' => $pesapalConfig,
            'pending' => $pending,
        ]);
    }

    /**
     * Legacy simulation endpoint for local testing.
     * On localhost, use this to simulate a successful payment without Pesapal.
     * Route: GET /tenant/payments/pesapal-callback?pending_id=X
     */
    public function simulateCallback(Request $request)
    {
        // Defense in depth: this route is only ever registered when app()->environment('local'),
        // but guard here too in case it's ever reached another way.
        abort_unless(app()->environment('local'), 404);

        $pendingId = $request->query('pending_id') ?? null;
        if ($pendingId) {
            $user = Auth::user();
            $pending = PendingPayment::find($pendingId);
            if ($pending && $user && $user->tenant && $pending->tenant_id === $user->tenant->id) {
                $invoice = Invoice::find($pending->invoice_id);
                if ($invoice) {
                    $payment = Payment::create([
                        'tenant_id' => $invoice->tenant_id,
                        'invoice_id' => $invoice->id,
                        'amount_paid' => $pending->amount,
                        'payment_reference' => Str::uuid()->toString(),
                        'payment_date' => now(),
                        'note' => 'Pesapal (simulated callback - local testing only)',
                    ]);
                    $pending->status = 'completed';
                    $pending->save();
                    return redirect('/tenant/payments')->with('success', "Payment of KES {$payment->amount_paid} recorded for Invoice {$invoice->invoice_number}.");
                }
            }
        }

        return redirect('/')->with('error', 'No pending payment found.');
    }

    /**
     * Webhook/Callback handler for Pesapal real callbacks.
     * Called by Pesapal POST to confirm payment completion.
     * Expects JSON payload and HMAC-SHA256 signature in header `X-Pesapal-Signature`.
     * Route: POST /payments/pesapal/callback
     * Full URL: config('app.url') . '/payments/pesapal/callback'
     */
    public function callback(Request $request, PesapalService $pesapal)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Pesapal-Signature');

        if (!$pesapal->verifySignature($signature, $payload)) {
            return response()->json(['message' => 'invalid signature'], 403);
        }

        $data = $request->json()->all();
        // Expected fields depend on Pesapal; try to extract reference and status
        $reference = $data['reference'] ?? $data['merchant_reference'] ?? null;
        $status = $data['status'] ?? ($data['payment_status'] ?? 'unknown');

        if (!$reference) {
            return response()->json(['message' => 'missing reference'], 400);
        }

        $pending = PendingPayment::where('reference', $reference)->first();
        if (!$pending) {
            // nothing to do
            return response()->json(['message' => 'pending not found'], 404);
        }

        if (in_array(strtolower($status), ['completed', 'paid', 'settled'])) {
            // Create Payment record (skip if this reference was already settled by a prior
            // delivery of this webhook or by the IPN handler, to avoid double-crediting)
            $invoice = Invoice::find($pending->invoice_id);
            if ($invoice && $pending->status !== 'completed') {
                $payment = Payment::create([
                    'tenant_id' => $pending->tenant_id,
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $pending->amount,
                    'payment_reference' => $reference,
                    'payment_date' => now(),
                    'note' => 'Pesapal callback',
                ]);
                $pending->status = 'completed';
                $pending->meta = array_merge($pending->meta ?? [], ['webhook' => $data]);
                $pending->save();
                return response()->json(['message' => 'recorded']);
            }

            if ($invoice) {
                return response()->json(['message' => 'already recorded']);
            }
        }

        $pending->status = 'failed';
        $pending->meta = array_merge($pending->meta ?? [], ['webhook' => $data]);
        $pending->save();
        return response()->json(['message' => 'updated']);
    }

    /**
     * IPN (Instant Payment Notification) listener for Pesapal.
     * Pesapal sends IPN to confirm payment settlements, especially for async flows.
     * Route: POST /payments/pesapal/ipn
     * Full URL: config('app.url') . '/payments/pesapal/ipn'
     */
    public function ipn(Request $request, PesapalService $pesapal)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Pesapal-Signature');

        // Verify signature
        if (!$pesapal->verifySignature($signature, $payload)) {
            return response()->json(['message' => 'invalid signature'], 403);
        }

        $data = $request->json()->all();
        $reference = $data['reference'] ?? $data['merchant_reference'] ?? null;
        $status = $data['status'] ?? ($data['payment_status'] ?? 'unknown');

        if (!$reference) {
            return response()->json(['message' => 'missing reference'], 400);
        }

        // Look up pending payment
        $pending = PendingPayment::where('reference', $reference)->first();
        if (!$pending) {
            return response()->json(['message' => 'pending not found'], 404);
        }

        // Handle status update
        if (in_array(strtolower($status), ['completed', 'paid', 'settled'])) {
            // Create Payment record if not already done
            $invoice = Invoice::find($pending->invoice_id);
            if ($invoice && $pending->status !== 'completed') {
                $payment = Payment::create([
                    'tenant_id' => $pending->tenant_id,
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $pending->amount,
                    'payment_reference' => $reference,
                    'payment_date' => now(),
                    'note' => 'Pesapal IPN notification',
                ]);
                $pending->status = 'completed';
            }
        } else {
            $pending->status = 'failed';
        }

        // Store IPN data in meta
        $pending->meta = array_merge($pending->meta ?? [], ['ipn' => $data]);
        $pending->save();

        return response()->json(['message' => 'processed'], 200);
    }

    /**
     * Browser redirect handler after tenant completes checkout on Pesapal.
     * Shows a brief thank-you popup then redirects tenant to their payments page.
     * This is intentionally simple — authoritative payment recording happens via the
     * webhook/IPN handlers which verify signatures. The browser return is UX-only.
     */
    public function callbackRedirect(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('merchant_reference') ?? null;
        $pendingId = $request->query('pending_id') ?? null;

        $message = 'Thank you — your payment is being processed. You will be redirected shortly.';

        // Optionally include small detail if available
        if ($reference) {
            $message = 'Thank you — payment reference ' . e($reference) . ' received. Processing now.';
        } elseif ($pendingId) {
            $message = 'Thank you — processing your payment now.';
        }

        return response()->view('pesapal.thankyou', ['message' => $message]);
    }
}
