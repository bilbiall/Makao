<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\MpesaTransaction;
use App\Services\MpesaService;

class MpesaController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Initiate M-Pesa STK push payment
     */
    public function initiate(Request $request, Invoice $invoice)
    {
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

        // Authorize: tenants must match; admins/staff can access
        $user = auth()->user();
        $isTenant = isset($user->tenant_id) && $user->tenant_id !== null;
        if ($isTenant && $transaction->tenant_id !== $user->tenant_id) {
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
            $transaction = MpesaTransaction::find($transactionId);
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
