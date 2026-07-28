<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Payment</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 500px; margin: 40px auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; font-size: 24px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        
        .info-box { background: #f9f9f9; border-left: 4px solid #007bff; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .info-box p { color: #555; margin: 8px 0; font-size: 14px; }
        .label { font-weight: 600; color: #333; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        
        .button-group { display: flex; gap: 10px; }
        button { flex: 1; padding: 12px; border: none; border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        
        .status { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .status.waiting { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #28a745; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 10px; vertical-align: middle; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .debug { background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin-top: 20px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; display: none; }
        .debug.show { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>M-Pesa Payment</h1>
        <p class="subtitle">Invoice {{ $invoice->invoice_number }}</p>

        @if(isset($error))
            <div class="status error">
                <strong>Error:</strong> {{ $error }}
            </div>
            @if(isset($transaction))
                <button onclick="history.back()" class="btn-secondary" style="width: 100%; padding: 10px;">Go Back</button>
            @endif
        @else
            <div class="info-box">
                <p><span class="label">Amount:</span> KES {{ number_format($transaction->amount, 2) }}</p>
                <p><span class="label">Phone:</span> {{ substr($transaction->phone_number, -10) }}</p>
                <p><span class="label">Reference:</span> {{ $transaction->reference }}</p>
            </div>

            <div class="status waiting">
                <span class="spinner"></span>
                <strong>Waiting for payment confirmation...</strong>
                <p style="margin-top: 8px; font-size: 13px;">Please complete the M-Pesa STK push prompt on your phone.</p>
            </div>

            <div id="statusContainer" style="display: none;"></div>

            <div class="button-group">
                <button class="btn-primary" onclick="checkStatus()">Check Status</button>
                <button class="btn-secondary" onclick="history.back()">Cancel</button>
            </div>

            @if(!config('app.debug'))
            @else
                <div style="margin-top: 20px; font-size: 12px; color: #666;">
                    <p><strong>Debug Mode:</strong> Transaction {{ $transaction->id }}</p>
                </div>
            @endif
        @endif
    </div>

    <script>
        let pollInterval;
        const transactionId = {{ $transaction->id ?? 'null' }};
        let pollCount = 0;
        const maxPolls = 120; // 2 minutes with 1-second intervals

        function checkStatus() {
            if (!transactionId) return;

            fetch(`/mpesa/status?transaction_id=${transactionId}`)
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    console.log('Status check response:', data);
                    if (data.result_status === 'completed') {
                        showSuccess();
                    } else if (data.result_status === 'failed') {
                        showFailed(data.reason || 'Payment was declined');
                    } else if (data.result_status === 'pending') {
                        showWaiting();
                    }
                })
                .catch(e => {
                    console.error('Status check error:', e);
                    // Continue polling on network errors
                });
        }

        function startPolling() {
            if (!transactionId) return;
            // Check immediately
            checkStatus();
            // Then check every 1 second
            pollInterval = setInterval(() => {
                pollCount++;
                checkStatus();
                if (pollCount >= maxPolls) {
                    clearInterval(pollInterval);
                    showTimeout();
                }
            }, 1000);
        }

        function showSuccess() {
            clearInterval(pollInterval);
            const container = document.getElementById('statusContainer');
            container.innerHTML = `<div class="status success"><strong>✓ Payment Confirmed!</strong> Redirecting to thank you page...</div>`;
            container.style.display = 'block';
            setTimeout(() => {
                window.location.href = `/mpesa/callback/redirect?transaction_id=${transactionId}`;
            }, 1000);
        }

        function showWaiting() {
            // Still waiting, polling continues
        }

        function showFailed(reason) {
            clearInterval(pollInterval);
            const container = document.getElementById('statusContainer');
            container.innerHTML = `<div class="status error"><strong>✗ Payment Failed:</strong> ${reason || 'Unknown reason'}</div>`;
            container.style.display = 'block';
        }

        function showTimeout() {
            const container = document.getElementById('statusContainer');
            container.innerHTML = `<div class="status waiting"><strong>Timeout:</strong> Payment request expired. Please try again.</div>`;
            container.style.display = 'block';
        }

        // Auto-check status periodically
        startPolling();
    </script>
</body>
</html>
