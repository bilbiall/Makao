<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\MpesaTransaction;
use App\Services\MpesaService;
use Illuminate\Support\Facades\Auth;

class MpesaController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /** True for account types that are allowed to act on any tenant's invoice/transaction
     *  within the landlord's own portfolio (already narrowed further by LandlordScope
     *  and, for Manager/Caretaker, by StaffScope elsewhere) - as opposed to a tenant, who
     *  may only ever act on their own. */
    protected function isStaff(?\App\Models\User $user): bool
    {
        return $user && ($user->isAdmin() || $user->isLandlord() || $user->isCaretaker() || $user->isManager() || $user->isSuperadmin());
    }

    /**
     * Initiate M-Pesa STK push payment
     */
    public function initiate(Request $request, Invoice $invoice)
    {
        $user = Auth::user();

        // A tenant may only pay their own invoice - mirrors PesapalController::initiate's
        // check, which this was previously missing, letting any authenticated tenant pass
        // another tenant's invoice ID and initiate a payment against it.
        if (!$this->isStaff($user) && (!$user?->tenant || $invoice->tenant_id !== $user->tenant->id)) {
            abort(403);
        }

        // Accept phone formats: 0712345678, +254712345678, 254712345678
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $invoice->balance,
            // Use # as delimiter to avoid conflicts with / characters
            'phone_number' => ['required', 'string', 'regex:#^(?:\\+?254|0)[0-9]{9}$#'],
        ], [
            'phone_number.regex' => 'Phone number must be in one of these formats: 0712345678, +254712345678, or 254712345678',
        ]);

        $result = $this->mpesaService->initiateStkPush(
            $invoice,
            $validated['phone_number'],
            (float) $validated['amount']
        );

        if (!$result['success']) {
            return view('mpesa.initiate', [
                'invoice' => $invoice,
                'error' => $result['error'] ?? 'Unknown error',
                'transaction' => $result['transaction'] ?? null,
            ]);
        }

        // Store transaction ID in session for polling
        session([
            'mpesa_transaction_id' => $result['transaction']->id,
            'mpesa_checkout_request_id' => $result['transaction']->checkout_request_id,
        ]);

        return view('mpesa.initiate', [
            'invoice' => $invoice,
            'transaction' => $result['transaction'],
            'response' => $result['response'] ?? null,
        ]);
    }

    /**
     * Poll transaction status (for JavaScript polling)
     */
    public function checkStatus(Request $request)
    {
        $transactionId = $request->query('transaction_id');
        if (!$transactionId) {
            return response()->json(['error' => 'Missing transaction ID'], 400);
        }

        $transaction = MpesaTransaction::find($transactionId);
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // Authorize: tenants must match their own tenant record; admins/staff can access
        // any transaction. Previously this only checked the mismatch case, which meant
        // an account with no tenant record at all (e.g. a "looking for a house" user
        // account, or a tenant record that failed to link) fell through unauthenticated
        // - fail closed instead: no tenant record and not staff means no access.
        $user = auth()->user();
        $tenant = $user?->tenant;

        if (!$tenant && !$this->isStaff($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($tenant && $transaction->tenant_id !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Refresh transaction from database (in case callback has updated it)
        $transaction->refresh();

        // If already completed via callback, return that immediately
        if ($transaction->status === 'completed') {
            \Log::info('Transaction already completed via callback', ['transaction_id' => $transactionId]);
            return response()->json([
                'success' => true,
                'status' => $transaction->status,
                'result_status' => 'completed',
            ]);
        }

        // If already failed, return that
        if ($transaction->status === 'failed') {
            return response()->json([
                'success' => false,
                'status' => $transaction->status,
                'result_status' => 'failed',
                'reason' => $transaction->response_message,
            ]);
        }

        // Still pending, query Safaricom for status
        $result = $this->mpesaService->queryTransactionStatus($transaction);

        // Refresh again after query (in case status was updated)
        $transaction->refresh();

        return response()->json([
            'success' => $result['success'],
            'status' => $transaction->status,
            'result_status' => $result['status'] ?? null,
        ]);
    }

    /**
     * Callback from Safaricom (M-Pesa confirmation)
     */
    public function callback(Request $request)
    {
        try {
            $data = $request->json()->all();
            \Log::info('M-Pesa callback received', ['data' => $data]);

            $this->mpesaService->handleCallback($data);

            // Respond to Safaricom with success
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        } catch (\Throwable $e) {
            \Log::error('M-Pesa callback error: ' . $e->getMessage());
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Error'], 500);
        }
    }

    /**
     * Browser redirect after STK push (success or cancel)
     */
    public function callbackRedirect(Request $request)
    {
        $transactionId = $request->query('transaction_id');
        $transaction = null;

        if ($transactionId) {
            $candidate = MpesaTransaction::find($transactionId);

            if ($candidate) {
                $user = auth()->user();
                $tenant = $user?->tenant;
                $owns = $tenant ? $candidate->tenant_id === $tenant->id : $this->isStaff($user);

                if ($owns) {
                    $transaction = $candidate;
                }
            }
        }

        // Check final status
        if ($transaction && $transaction->status === 'pending') {
            $result = $this->mpesaService->queryTransactionStatus($transaction);
        }

        return view('mpesa.thankyou', [
            'transaction' => $transaction,
            'success' => $transaction && $transaction->status === 'completed',
        ]);
    }
}
