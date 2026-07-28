# M-Pesa Daraja API Integration

This document covers the M-Pesa STK Push integration using Safaricom's Daraja API v2.

## Overview

The M-Pesa payment flow is completely separate from Pesapal and works as follows:

1. Tenant clicks **Pay Now** on an invoice
2. Selects **M-Pesa (STK Push)** payment method
3. Enters amount (editable) and phone number
4. System initiates STK push via Daraja API
5. M-Pesa prompt appears on tenant's phone
6. Tenant enters PIN to confirm
7. Safaricom callback notifies system of payment
8. System creates Payment record, updates invoice balance, sends SMS

## Setup

### 1. Obtain Daraja API Credentials

1. Go to [Daraja Developer Portal](https://developer.safaricom.co.ke)
2. Register your application
3. Navigate to **My Apps** and select your app
4. Under **Test Credentials**, copy:
   - **Consumer Key** (API Key)
   - **Consumer Secret** (API Secret)
   - **Business Short Code** (e.g., 174379 for test)
   - **Passkey** (M-Pesa Online Passkey for test)

### 2. Configure M-Pesa in Settings

1. Go to **Admin > Settings > Payments** tab
2. Under **M-Pesa (Daraja API)** section, fill in:
   - **Daraja API Key**: Your Consumer Key
   - **Daraja API Secret**: Your Consumer Secret
   - **Business Short Code**: Your short code (e.g., 174379)
   - **M-Pesa Online Passkey**: Your passkey
   - **Use Sandbox (Daraja Test)**: Toggle ON for testing, OFF for production
   - **Currency**: KES (default)
3. Save settings

### 3. API Endpoints

#### Token Endpoint
- **Sandbox**: `https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials`
- **Live**: Same endpoint (authentication via credentials determines environment)

#### STK Push Endpoint
- **Sandbox & Live**: `https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest`

#### STK Push Query Endpoint
- **Sandbox & Live**: `https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query`

### 4. Callback URL Configuration

In your M-Pesa merchant dashboard (Safaricom portal), configure the callback URL:
- **Callback URL**: `https://yourdomain.com/api/mpesa/callback`

This is where Safaricom will POST payment confirmations.

## Usage

### Tenant Payment Flow

1. Tenant views invoice in **Tenant Portal > Invoices**
2. Clicks **Pay Now** button
3. Modal opens with:
   - **Payment Method**: Dropdown (M-Pesa or Pesapal)
   - **Amount**: Editable input (defaults to invoice balance)
   - **Phone Number**: Input for M-Pesa recipient
4. Selects **M-Pesa (STK Push)**
5. Enters phone number and amount
6. Clicks **Continue to Payment**
7. System initiates STK push
8. Browser polls `/tenant/mpesa/status?transaction_id=X` every 1 second
9. On Daraja API response, displays "Waiting for payment confirmation"
10. Safaricom sends callback to `/api/mpesa/callback`
11. System creates Payment, updates invoice balance
12. Browser detects success via status polling
13. Redirects to `/mpesa/callback/redirect?transaction_id=X`
14. Thank you modal displays for 4 seconds before auto-redirecting to `/tenant/payments`

### Admin Dashboard

Admins can view all M-Pesa transactions:
- **Admin > M-Pesa Transactions**
- View transaction details, status, response codes
- Filter by status, tenant, date

## Models & Database

### MpesaTransaction Model

Fields:
- `id`: Primary key
- `invoice_id`: Foreign key to invoices
- `tenant_id`: Foreign key to tenants
- `house_id`: Foreign key to houses
- `amount`: Payment amount (decimal)
- `phone_number`: Phone number used (254-formatted)
- `reference`: Unique transaction reference (INV-{invoice_id}-{random})
- `checkout_request_id`: ID from Daraja STK push response
- `status`: pending | completed | failed | timeout
- `response_code`: Response from initial STK push request
- `response_message`: Response description from STK push
- `receipt_number`: M-Pesa receipt number (from callback)
- `result_code`: Result code from callback (0 = success)
- `result_desc`: Result description from callback
- `meta`: JSON metadata (stk_request, stk_response, etc.)
- `timestamps`: created_at, updated_at

### Payment Model Integration

When M-Pesa transaction completes, a Payment record is created:
- `invoice_id`: References the invoice
- `tenant_id`: References the tenant
- `amount_paid`: Transaction amount
- `payment_method`: 'mpesa'
- `payment_reference`: M-Pesa receipt number or transaction reference
- `transaction_id`: Checkout request ID
- `status`: 'completed'
- `payment_date`: Current timestamp

The invoice balance is automatically decremented.

## File Structure

```
app/
  Services/
    MpesaService.php               # Core Daraja API service
  Http/
    Controllers/
      MpesaController.php          # Routes handler
  Models/
    MpesaTransaction.php           # Transaction model
  Filament/
    Resources/
      MpesaTransactionResource.php # Admin UI for transactions
      MpesaTransactionResource/
        Pages/
          ListMpesaTransactions.php
          ViewMpesaTransaction.php
    Pages/
      Settings.php                 # M-Pesa settings fields
  Tenant/
    Resources/
      InvoiceResource.php          # Updated Pay action with M-Pesa

resources/
  views/
    mpesa/
      initiate.blade.php           # STK push prompt & polling UI
      thankyou.blade.php           # Thank you modal with 4-sec redirect

routes/
  web.php                          # M-Pesa web routes
  api.php                          # M-Pesa callback endpoint

database/
  migrations/
    2025_12_27_000002_create_mpesa_transactions_table.php
```

## Key Features

### Phone Number Formatting
M-Pesa requires phone numbers in 254XXXXXXXXX format:
- Input: `0712345678` → Stored/Sent: `254712345678`
- Input: `254712345678` → Stored/Sent: `254712345678`
- Input: `+254712345678` → Stored/Sent: `254712345678`

The `MpesaService::formatPhoneNumber()` method handles this.

### Status Polling

The `initiate.blade.php` view uses JavaScript to poll `/tenant/mpesa/status` every 1 second with a max of 120 polls (2 minutes). This allows:
- Immediate detection of STK push initiation success/failure
- Periodic checking for payment completion
- Graceful timeout after 2 minutes

The polling endpoint queries the transaction and returns:
```json
{
  "success": true,
  "status": "pending|completed|failed",
  "result_status": "pending|completed|failed"
}
```

### OAuth Token Management

Daraja API requires Bearer token authentication. `MpesaService::getAccessToken()` handles this:
- Authenticates via Basic Auth (Consumer Key:Secret)
- Requests access token from `/oauth/v1/generate`
- Caches token in memory (request-scoped)
- Token valid for 1 hour

### Callback Handling

Safaricom POSTs to `/api/mpesa/callback` with STK callback data:
```json
{
  "Body": {
    "stkCallback": {
      "CheckoutRequestID": "...",
      "ResultCode": 0,
      "ResultDesc": "...",
      "CallbackMetadata": {
        "Item": [
          { "Name": "Amount", "Value": 1000 },
          { "Name": "MpesaReceiptNumber", "Value": "..." },
          { "Name": "PhoneNumber", "Value": "254712345678" }
        ]
      }
    }
  }
}
```

The `MpesaService::handleCallback()` method:
- Parses the callback data
- Updates MpesaTransaction status and receipt
- Creates Payment record
- Updates invoice balance
- Sends SMS confirmation
- Returns success to Safaricom

## Testing

### Sandbox Testing

1. Ensure **Use Sandbox** is toggled ON in settings
2. Use sandbox credentials (Consumer Key/Secret/Shortcode/Passkey)
3. Initiate payment with any amount (sandbox accepts any amount)
4. For testing without real M-Pesa, manually trigger the callback:

```bash
curl -X POST http://localhost:8000/api/mpesa/callback \
  -H "Content-Type: application/json" \
  -d '{
    "Body": {
      "stkCallback": {
        "CheckoutRequestID": "CHECKOUT_ID_HERE",
        "ResultCode": 0,
        "ResultDesc": "The service request has been processed successfully.",
        "CallbackMetadata": {
          "Item": [
            {"Name": "Amount", "Value": 100},
            {"Name": "MpesaReceiptNumber", "Value": "LHG31AA1V69"},
            {"Name": "PhoneNumber", "Value": "254712345678"}
          ]
        }
      }
    }
  }'
```

### Production Testing

1. Toggle **Use Sandbox** OFF
2. Enter live credentials from your M-Pesa merchant account
3. Use production short code
4. Test with small amounts first (e.g., KES 1)

## Troubleshooting

### "OAuth token request failed"
- **Cause**: Invalid Consumer Key or Secret
- **Fix**: Verify credentials in Admin Settings > Payments > M-Pesa section

### "STK push failed: INVALID_SHORTCODE"
- **Cause**: Business short code doesn't exist or is invalid
- **Fix**: Verify short code matches your Daraja credentials

### "Payment status shows 'pending' forever"
- **Cause 1**: Callback URL not registered with Safaricom
  - **Fix**: Add callback URL to M-Pesa merchant dashboard
- **Cause 2**: Firewall blocking Safaricom POST requests
  - **Fix**: Whitelist Safaricom IP ranges (check Daraja docs)
- **Cause 3**: Phone number format incorrect
  - **Fix**: Verify phone number is 254XXXXXXXXX format

### Admin not seeing M-Pesa Transactions
- **Cause**: Filament resource not registered
- **Fix**: Navigate to Admin > M-Pesa Transactions (should appear automatically)

## Next Steps

1. Fill M-Pesa settings in Admin > Settings > Payments
2. Test with sandbox credentials first
3. Verify callback URL in Safaricom merchant dashboard
4. Do a test payment on sandbox
5. Promote to production credentials when ready
6. Keep Pesapal as fallback payment method if desired

## References

- [Safaricom Daraja API Docs](https://developer.safaricom.co.ke/)
- [STK Push Guide](https://developer.safaricom.co.ke/docs?java#lipa-na-m-pesa-online)
- [M-Pesa Query Guide](https://developer.safaricom.co.ke/docs?java#query-the-status-of-an-m-pesa-stk-push)
