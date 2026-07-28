<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesapal Payment Initiation</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:#f7f7f8; color:#111; }
        .card { max-width:720px;margin:48px auto;padding:24px;border:1px solid #eee;border-radius:8px;background:#fff }
        code.box { background:#f3f4f6;padding:8px;display:block;margin:8px 0;word-break:break-all; }
        .btn { display:inline-block;padding:10px 16px;border-radius:6px;text-decoration:none;color:#fff }
        .btn-green { background:#10b981 }
        .notice { padding:12px;border-radius:6px;margin-bottom:12px }
        .notice.warn { background:#fff3cd;border:1px solid #ffeeba }
        .notice.ok { background:#d1fae5;border:1px solid #6ee7b7 }
    </style>
</head>
<body>
    <main class="card">
        <h2>Pesapal Payment Initiation (Simulation)</h2>
        <p><strong>Invoice:</strong> {{ $invoice->invoice_number }} — <strong>Amount:</strong> KES {{ number_format($amount, 2) }}</p>

        @if(!empty($pesapal) && !empty($pesapal['consumer_key']))
            <div class="notice ok">
                <strong>✓ Pesapal configured</strong> — Real checkout should have redirected here. If you see this, check your API keys.
            </div>
        @else
            <div class="notice warn">
                <strong>Notice:</strong> Pesapal credentials are not configured. Use the simulation below for local testing.
            </div>
        @endif

        @if(app()->environment() !== 'production' && !empty($pending->meta))
            <h4>Debug Info (non-production)</h4>
            @if(!empty($pending->meta['pesapal_error']))
                <div class="notice warn">
                    <strong>Error:</strong> {{ $pending->meta['pesapal_error'] }}
                </div>
            @endif
            @if(!empty($pending->meta['pesapal_response']))
                <div style="margin-top:8px">
                    <strong>Pesapal Response:</strong>
                    <pre style="background:#f3f4f6;padding:8px;border-radius:6px;max-height:220px;overflow:auto">{{ json_encode($pending->meta['pesapal_response'], JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        @endif

        <h3>Callback & IPN URLs (for Pesapal Dashboard)</h3>
        <p>When deploying to production, configure these URLs in your Pesapal merchant dashboard:</p>
        <ul>
            <li><strong>Callback URL:</strong> <code class="box">{{ config('app.url') }}/api/payments/pesapal/callback</code></li>
            <li><strong>IPN Listener URL:</strong> <code class="box">{{ config('app.url') }}/api/payments/pesapal/ipn</code></li>
        </ul>
        <p style="font-size:90%;color:#6b7280;">
            <strong>Note:</strong> On localhost, Pesapal cannot reach <code>http://localhost:8000</code>. Use ngrok or deploy to a public server to test real payments.
        </p>

        <h3>Local Testing (Simulation)</h3>
        <p>Click the button below to simulate a successful payment without Pesapal:</p>

        @if(!empty($pending))
            <a class="btn btn-green" href="{{ route('tenant.payments.pesapal.callback') }}?pending_id={{ $pending->id }}">Simulate Successful Payment</a>
        @else
            <a class="btn btn-green" href="{{ route('tenant.payments.pesapal.callback') }}">Simulate Successful Payment</a>
        @endif

        <p style="margin-top:14px;color:#6b7280;font-size:90%;">After simulation, a Payment record is created and invoice totals update automatically.</p>
    </main>
</body>
</html>

