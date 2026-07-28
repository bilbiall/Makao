<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thank You</title>
    <style>
        body{font-family:Inter,system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial,sans-serif;background:#f7fafc;margin:0;height:100vh;display:flex;align-items:center;justify-content:center}
        .card{background:white;padding:2rem;border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,.08);max-width:420px;text-align:center}
        .title{font-size:1.25rem;margin-bottom:.5rem}
        .message{color:#4b5563;margin-bottom:1rem}
        .link{display:inline-block;padding:.5rem 1rem;background:#2563eb;color:white;border-radius:8px;text-decoration:none}
    </style>
</head>
<body>
    <div class="card" role="status" aria-live="polite">
        <div class="title">Thank you</div>
        <div class="message">{{ $message ?? 'Thank you — your payment is being processed.' }}</div>
        <a class="link" href="/tenant/payments">Go to Payments now</a>
        <div style="margin-top:12px;color:#9ca3af;font-size:0.9rem">You will be redirected in <span id="count">4</span>s…</div>
    </div>

    <script>
        (function(){
            var count = 4;
            var el = document.getElementById('count');
            var t = setInterval(function(){
                count--;
                if(el) el.textContent = count;
                if(count <= 0){
                    clearInterval(t);
                    window.location.href = '/tenant/payments';
                }
            }, 1000);
        })();
    </script>
</body>
</html>