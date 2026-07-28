# Pesapal Payment Integration Guide

## Overview

This Laravel application integrates with Pesapal for payment processing. The integration uses environment-based URLs that automatically work on localhost and scale to production without code changes.

## Route Structure

All Pesapal payment routes are defined once and use the same paths across environments:

### Callback & IPN Endpoints

**Callback Endpoint (Payment Status):**
```
POST /api/payments/pesapal/callback
```

**IPN Listener Endpoint (Instant Payment Notification):**
```
POST /api/payments/pesapal/ipn
```

### Environment-Based URLs

The full URLs are built dynamically from `config('app.url')`:

#### Local Development
```
.env:
APP_URL=http://localhost:8000

Callback: http://localhost:8000/api/payments/pesapal/callback
IPN:      http://localhost:8000/api/payments/pesapal/ipn
```

#### Production
```
.env:
APP_URL=https://yourdomain.com

Callback: https://yourdomain.com/api/payments/pesapal/callback
IPN:      https://yourdomain.com/api/payments/pesapal/ipn
```

**No code changes required when deploying** — only the `.env` file needs to be updated.

## Pesapal Merchant Dashboard Configuration

1. Log in to your Pesapal merchant dashboard
2. Go to **Settings > API Configuration**
3. Set the following URLs:
   - **Callback URL:** `https://yourdomain.com/api/payments/pesapal/callback`
   - **IPN Listener URL:** `https://yourdomain.com/api/payments/pesapal/ipn`

These URLs will be displayed in the payment initiation page for reference.

## Pesapal API Endpoints

The application uses **Pesapal v3 API** with environment-aware endpoints:

### Sandbox (Demo) Endpoints
```
Base URL: https://cybqa.pesapal.com/pesapalv3
Token URL: https://cybqa.pesapal.com/pesapalv3/oauth/token
Checkout: https://cybqa.pesapal.com/pesapalv3/merchants/submit-order
```
Use sandbox credentials from [Pesapal Developer Portal](https://developer.pesapal.com)

### Live (Production) Endpoints
```
Base URL: https://pay.pesapal.com/v3
Token URL: https://pay.pesapal.com/v3/oauth/token
Checkout: https://pay.pesapal.com/v3/merchants/submit-order
```
Use live credentials when ready to accept real payments.

⚠️ **Important:** The old `demo.pesapal.com` domain is **deprecated** and has expired SSL certificates. Always use `cybqa.pesapal.com` for sandbox and `pay.pesapal.com` for live.

## Admin Settings

Configure Pesapal credentials in **Admin > Settings > Payments tab**:

- **Consumer Key:** Your Pesapal API consumer key (from [developer.pesapal.com](https://developer.pesapal.com))
- **Consumer Secret:** Your Pesapal API consumer secret (keep this secret!)
- **Pesapal IPN ID:** (Required for v3) Register via `php artisan pesapal:register-ipn` — this retrieves the IPN ID from Pesapal API
- **Webhook Secret:** Your Pesapal webhook HMAC secret (for signature verification)
- **Callback URL:** (Optional) Override the default callback URL
- **Use Pesapal Sandbox:** Toggle to **ON** for sandbox (cybqa.pesapal.com), **OFF** for live (pay.pesapal.com)
- **Currency:** Default is KES

**Important:** The **Pesapal IPN ID** is mandatory for v3 checkout. Without it, Pesapal returns HTTP 200 with an empty body and no checkout URL.

**Ensure the toggle matches your credentials:**
- Sandbox credentials + Sandbox ON + Sandbox IPN ID ✓
- Live credentials + Sandbox OFF + Live IPN ID ✓
- Sandbox credentials + Sandbox OFF ✗ (will fail with 401)
- Live credentials + Sandbox ON ✗ (will fail with 401)
- Missing IPN ID ✗ (HTTP 200, empty body, no checkout)

## Local Testing (Localhost)

**Important:** Pesapal cannot directly call `http://localhost:8000`.

### Option 1: Use ngrok (Recommended)
```bash
# Install ngrok: https://ngrok.com/download
ngrok http 8000

# You'll get a public URL like: https://abc123.ngrok.io
# Update .env:
APP_URL=https://abc123.ngrok.io

# Now configure Pesapal dashboard with:
# Callback: https://abc123.ngrok.io/api/payments/pesapal/callback
# IPN:      https://abc123.ngrok.io/api/payments/pesapal/ipn
```

### Option 2: Use Simulation Mode (for development)
Click "Simulate Successful Payment" on the payment initiation page to test locally without Pesapal credentials.

## Payment Flow

### 1. Tenant Initiates Payment
- Tenant clicks "Pay Now" on invoice
- Creates a `PendingPayment` record
- Redirects to Pesapal checkout (if configured) or simulation page

### 2. Pesapal Processes Payment
- Tenant completes payment on Pesapal
- Pesapal sends callback/IPN to configured URL

### 3. Application Records Payment
- Callback/IPN handler verifies HMAC signature
- Creates a `Payment` record
- Updates invoice status and tenant balance
- Sends SMS confirmation to tenant

## Signature Verification

All callbacks and IPN messages from Pesapal include an `X-Pesapal-Signature` header with an HMAC-SHA256 signature.

The application verifies this signature using the `webhook_secret` configured in the Pesapal settings:

```php
// In PesapalController
$signature = $request->header('X-Pesapal-Signature');
if (!$pesapal->verifySignature($signature, $payload)) {
    return response()->json(['message' => 'invalid signature'], 403);
}
```

## Files & Structure

```
app/
  Http/Controllers/
    PesapalController.php         # Payment callbacks and IPN handlers
  Models/
    PendingPayment.php            # Stores in-progress payments
    Payment.php                   # Completed payments (existing)
  Services/
    PesapalService.php            # API client and signature verification
  Filament/
    Resources/
      PendingPaymentResource.php  # Admin UI for tracking pending payments
    Pages/
      Settings.php                # Admin settings for Pesapal credentials

routes/
  api.php                         # Callback and IPN routes (unauthenticated)
  web.php                         # Payment initiation routes (authenticated)

resources/
  views/
    pesapal/
      initiate.blade.php          # Payment initiation page

database/
  migrations/
    2025_12_27_000001_create_pending_payments_table.php
```

## Testing Checklist

**Before testing checkout, ensure:**
- [ ] Consumer Key and Secret are entered in Admin Settings
- [ ] IPN ID is registered and saved (run `php artisan pesapal:register-ipn`)
- [ ] Sandbox toggle matches your credentials
- [ ] APP_URL is set correctly in `.env`

**Then test:**

- [ ] Pesapal credentials entered in Admin Settings
- [ ] `APP_URL` set correctly in `.env`
- [ ] Callback/IPN URLs configured in Pesapal dashboard
- [ ] Test simulation on localhost (no credentials needed)
- [ ] Test with ngrok + sandbox credentials
- [ ] Verify SMS confirmation sent after payment
- [ ] Check `PendingPayment` and `Payment` records in admin UI
- [ ] Deploy to production with updated `.env` and Pesapal URLs

## Troubleshooting

### "HTTP 200 with empty response body, no checkout URL"
- **Root cause:** Missing or invalid Pesapal IPN ID (required for v3).
- **Solution:**
  1. Verify `pesapal.ipn_id` is set in Admin Settings (not empty).
  2. If missing, run:
     ```bash
     php artisan pesapal:register-ipn
     ```
  3. Ensure the IPN ID is from the same environment (sandbox IPN ID for sandbox, live for live).
  4. Verify the IPN URL in Pesapal matches `config('app.url') . '/api/payments/pesapal/ipn'`.

### "cURL error 60: SSL certificate ... certificate has expired"
- **Cause:** Using the deprecated `demo.pesapal.com` domain (old endpoint with expired SSL cert).
- **Solution:** Ensure `pesapal.sandbox` setting is correctly toggled:
  - **Sandbox ON** → Uses `https://cybqa.pesapal.com/pesapalv3` (correct)
  - **Sandbox OFF** → Uses `https://pay.pesapal.com/v3` (correct)
  - Never use `demo.pesapal.com` — it is deprecated and non-functional.

### "Pesapal checkout never appears, shows simulation instead"
- Check Laravel logs: `tail storage/logs/laravel.log`
- View the **Debug Info** section on the payment initiation page (non-production only)
- Inspect `PendingPayment.meta` in the admin UI under Pending Payments — check `pesapal_error`, `pesapal_raw_response`, or `pesapal_attempts`
- Common causes:
  - Missing IPN ID (see above)
  - SSL certificate error (see above)
  - Invalid `consumer_key` or `consumer_secret`
  - Sandbox/live mismatch (toggle doesn't match credentials)
  - Network timeout — server cannot reach Pesapal

### "No pending payment found"
- Check that the `PendingPayment` record was created
- Verify the reference matches between callback and pending record
- Check admin UI under "Pending Payments"

### "Invalid signature"
- Ensure `webhook_secret` in settings matches Pesapal dashboard
- Verify HTTPS is used in production (callbacks must be over HTTPS)

### "Pesapal redirecting back immediately"
- Check `consumer_key` and `consumer_secret` are correct
- Verify sandbox toggle matches your credentials (sandbox ON for sandbox creds, OFF for live creds)
- Verify IPN ID is set and valid
- Review Pesapal API response in `PendingPayment` meta field (admin UI)

## Next Steps

1. Obtain Pesapal sandbox credentials from [Pesapal Developer Portal](https://developer.pesapal.com)
2. Enter Consumer Key and Consumer Secret in **Admin > Settings > Payments tab**
3. **Register Pesapal IPN ID** (required for v3):
   ```bash
   php artisan pesapal:register-ipn
   ```
   This command:
   - Requests an OAuth token using your credentials
   - Registers your IPN URL via Pesapal API
   - Retrieves the IPN ID
   - Saves it to your settings automatically
4. Set **Use Pesapal Sandbox** toggle **ON** (sandbox credentials) or **OFF** (live credentials)
5. Trigger a payment from tenant invoice — confirm Pesapal checkout appears
6. Verify debug info in `PendingPayment.meta` (admin UI) if issues occur
7. Deploy to production and update `.env` with production domain
8. Re-run `php artisan pesapal:register-ipn` in production (will register live IPN ID)
9. Update Pesapal dashboard with production callback/IPN URLs
10. Test live payments
