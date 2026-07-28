<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        
        .modal { background: white; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); padding: 40px; text-align: center; max-width: 400px; }
        .icon { font-size: 60px; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 10px; font-size: 28px; }
        p { color: #666; margin-bottom: 8px; font-size: 16px; }
        
        .success .icon { color: #28a745; }
        .success h1 { color: #28a745; }
        
        .failed .icon { color: #dc3545; }
        .failed h1 { color: #dc3545; }
        
        .countdown { font-size: 14px; color: #999; margin-top: 20px; }
        .redirect-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; font-weight: 600; }
        .redirect-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    @if($success)
        <div class="modal success">
            <div class="icon">✓</div>
            <h1>Payment Successful</h1>
            <p>Your payment has been confirmed.</p>
            <p>Thank you for your payment!</p>
            <div class="countdown">Redirecting in <span id="countdown">4</span> seconds...</div>
            @php
                $user = auth()->user();
                $successRedirect = ($user && ($user->role ?? null) !== 'tenant') ? '/admin/payments' : '/tenant/payments';
            @endphp
            <a href="{{ $successRedirect }}" class="redirect-link">Click here if not redirected</a>
        </div>
    @else
        <div class="modal failed">
            <div class="icon">✕</div>
            <h1>Payment Failed</h1>
            <p>{{ $transaction?->result_desc ?? 'Your payment could not be processed.' }}</p>
            @php
                $user = auth()->user();
                $failRedirect = ($user && ($user->role ?? null) !== 'tenant') ? '/admin/invoices' : '/tenant/invoices';
            @endphp
            <a href="{{ $failRedirect }}" class="redirect-link">Return to Invoices</a>
        </div>
    @endif

    <script>
        @if($success)
        let countdown = 4;
        const interval = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = '{{ $successRedirect }}';
            }
        }, 1000);
        @endif
    </script>
</body>
</html>
