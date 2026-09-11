# Setting up M-Pesa (Safaricom Daraja) - STK Push & C2B

A step-by-step walkthrough for taking a property's M-Pesa payments live in this app -
from creating a Daraja developer account, through sandbox testing, to a real Paybill/
Till taking real payments. This supersedes `MPESA_INTEGRATION.md` and
`C2BFORRENT.md` (both now removed) - one of those had a stale claim about which URL
sandbox uses, corrected below.

## The two payment flows, in one sentence each

- **STK Push ("Pay Now")** - the tenant clicks Pay Now on an invoice in the app, gets
  an M-Pesa prompt on their phone, enters their PIN, done. The app initiates this.
- **C2B (Paybill reconciliation)** - a tenant pays your Paybill directly from their own
  M-Pesa app (no "Pay Now" click at all), and this app automatically detects and
  credits it by matching the Account Number (or phone number) they used.

Both are configured from the same place - an **M-Pesa Channel**
(**Admin > M-Pesa Setup (Advanced)**, or the Filament "M-Pesa Channels" resource) -
because both use the same Daraja app credentials for a given Paybill/Till. You can use
STK push alone, C2B alone, or both together on the same channel.

Every landlord/property brings their **own** Paybill/Till and Daraja credentials -
there's no shared platform-wide till. One M-Pesa Channel can apply to all of a
landlord's properties, or just one specific property (see "Scoping to a property or
your whole portfolio" below).

## Before you start

- A [Safaricom Daraja](https://developer.safaricom.co.ke) developer account - free,
  just an email signup. This gets you **sandbox** access immediately, with no real
  Paybill needed yet.
- For **sandbox testing**: nothing else - Safaricom gives you a free test shortcode and
  test credentials.
- For **going live**: a real Paybill or Till number (from Safaricom directly, or from
  the bank/service that issued it), and a Daraja app that's been through Safaricom's
  "Go Live" approval process for that Paybill.
- Your Renty/Makao site must be reachable over **HTTPS** at a real domain before you
  can go live (not `localhost`) - the app enforces this and will refuse to send a live
  STK push otherwise.

## Local testing (XAMPP / `localhost`) - do this before Step 1

Safaricom's servers call **your** app back to deliver results (the STK result, and every
C2B payment) - they POST to a URL over the public internet. If your site is running at
`http://localhost/renty/public` (the default XAMPP setup), Safaricom cannot reach it: the
request will look "sent" from Daraja's side but nothing will ever arrive back, even with
perfectly correct Consumer Key/Secret/Passkey. This is the single most common reason
someone gets "stuck" testing C2B despite following every other step correctly - the app
now also shows a warning banner on the M-Pesa Channel form when it detects this.

Fix it with a tunnel (`ngrok` is the standard choice, free tier is enough):

1. Download `ngrok` from [ngrok.com/download](https://ngrok.com/download), unzip it
   anywhere, and run `ngrok config add-authtoken <your-token>` once (free account,
   token shown on your ngrok dashboard).
2. With XAMPP's Apache running as usual, open a terminal and run:
   ```
   ngrok http 80
   ```
   (use whatever port your XAMPP Apache actually listens on - 80 is the default).
3. ngrok prints a `Forwarding` line like `https://a1b2-c3d4.ngrok-free.app -> http://localhost:80`.
   Copy that `https://...ngrok-free.app` URL.
4. In `.env`, temporarily set:
   ```
   APP_URL=https://a1b2-c3d4.ngrok-free.app/renty/public
   ```
   then run `php artisan config:clear` (Laravel caches `APP_URL` - editing `.env` alone
   isn't enough if config is cached).
5. Now proceed with the rest of this guide as normal - STK's callback URL and C2B's
   register-URL step both read `APP_URL`, so they'll now point at your tunnel and
   Safaricom's calls will actually reach your local app.
6. **The ngrok URL changes every time you restart it** (on the free tier) - if you stop
   and restart `ngrok`, repeat steps 3-4 with the new URL, and if C2B was already
   registered, click **Register C2B** again so Safaricom has the new address.
7. Once you go live with a real domain (see "Going live" below), switch `APP_URL` back
   to that real HTTPS domain and stop using ngrok - the tunnel is a testing tool only.

## Step 1 - Create a Daraja app and get sandbox credentials

1. Go to [developer.safaricom.co.ke](https://developer.safaricom.co.ke) and create an
   account (or log in).
2. Go to **My Apps** and create a new app. Give it any name.
3. Under your app's **Test Credentials** / **Sandbox** tab, note down:
   - **Consumer Key**
   - **Consumer Secret**
   - The standard sandbox **Business Short Code**: `174379`
   - The standard sandbox **Passkey** (Safaricom publishes this on the same page -
     it's the same for every sandbox app, unlike your Consumer Key/Secret).
4. Under your app's product list, add **Lipa Na M-Pesa Sandbox** if it isn't already
   attached - this is what gives you the C2B-specific sandbox shortcode (a different,
   `6xxxxx`-style number) if you want to test C2B separately from STK. For STK-only
   testing, the `174379` shortcode above is enough.

Nothing here touches real money - sandbox is a fully separate Safaricom environment.

## Step 2 - Create an M-Pesa Channel in the app

In the app: **Admin (or Superadmin, if you're setting this up on a landlord's behalf)
> M-Pesa Channels > Create**. Fill in:

| Field | What to enter |
|---|---|
| Label | Anything memorable, e.g. "Kilimani Apartments Paybill" |
| Applies to | Leave blank for now (see scoping section below) |
| Paybill / Till Number | `174379` for sandbox testing |
| Consumer Key | From Step 1 |
| Consumer Secret | From Step 1 |
| Use Sandbox | **ON** |
| Use for "Pay Now" (STK push) | **ON** |
| M-Pesa Online Passkey | The sandbox passkey from Step 1 (optional in sandbox, required once live) |

Save. STK push is now live in sandbox - no separate "activation" step needed for STK.

> **Setting this up for a landlord who isn't you:** as superadmin, go to
> **Superadmin > Landlords**, open their row, and use the **M-Pesa Channels** action -
> it's the exact same form, just with a Landlord picker at the top.

## Step 3 - Test STK push in sandbox

1. As a tenant (or via **Admin > Invoices**), open an unpaid invoice and click
   **Pay Now**, choosing M-Pesa.
2. Enter Safaricom's official sandbox test phone number: **254708374149** (this is a
   fixed number Safaricom's sandbox always treats as "successful" - it doesn't ring a
   real phone).
3. Submit. The app calls Daraja's STK push endpoint and starts polling for a result.
4. Sandbox auto-completes the "prompt" - within a few seconds you should see the
   invoice marked paid and a Payment record created.

If it fails immediately, see Troubleshooting below - the most common cause at this
stage is a copy-pasted Consumer Key/Secret with a stray space or a wrong Passkey.

## Step 4 - Set up C2B (optional, only if tenants pay your Paybill directly)

C2B is **off for every landlord by default** and gated at the founder level, since a
misrouted real payment is a higher-stakes mistake than a rejected STK push.

1. **You (superadmin) enable it first**: **Superadmin > Landlords > edit** that
   landlord, toggle **C2B (Paybill) reconciliation enabled**, save. Until this is on,
   the landlord won't see a way to register C2B at all, even though their channel
   already exists from Step 2.
2. **Open the channel** you created in Step 2 (it must be a real **Paybill**, not a
   Till - Tills have no Account Number field, so there's nothing for C2B to match a
   tenant against).
3. Click **Register C2B**. This calls Daraja's `registerurl` API, telling Safaricom to
   start sending Paybill confirmations here for that shortcode. On success the channel
   shows "Registered with Safaricom on `<date>`".
4. **Give each tenant an Account Number to quote when they pay**: every tenant has a
   `payment_account_code` field (**Admin > Tenants > edit**, labeled "M-Pesa Account
   Number"), defaulting to their unit name. Tell tenants to type this exactly as their
   Paybill Account Number. If a landlord skips this, matching still falls back to the
   tenant's phone number.

### How matching works when a payment comes in

1. Safaricom POSTs a **Confirmation** to this app for that shortcode.
2. The app tries the Account Number the payer typed against `payment_account_code`
   first, then their phone number, in that order.
3. A confident match creates a Payment automatically - invoice balance, tenant
   balance, SMS receipt, all the same as an STK payment.
4. No confident match -> the payment lands as **needs_review** in
   **Admin > M-Pesa Channels > C2B Payments** rather than being guessed. Nothing is
   ever silently lost - every inbound payment appears in that list either way.

### Testing C2B in sandbox

**Best option - Safaricom's own simulator** (behaves like a real Paybill payment):

```bash
curl -X POST https://sandbox.safaricom.co.ke/mpesa/c2b/v2/simulate \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "ShortCode": "<your channel'"'"'s shortcode>",
    "CommandID": "CustomerPayBillOnline",
    "Amount": "500",
    "Msisdn": "254708374149",
    "BillRefNumber": "<a tenant'"'"'s payment_account_code>"
  }'
```

Get `<ACCESS_TOKEN>` the same way STK push does - `POST` Basic Auth
(`consumer_key:consumer_secret`) to
`https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials`.
Safaricom will call your registered Confirmation URL for real, exactly like a live
payment.

**Faster option - call the local webhook directly** (skips Safaricom entirely, good
for testing the matching logic itself):

```bash
TRANS_ID="TEST-$(date +%s)"

curl -X POST https://your-domain.example/api/mpesa/c2b/confirmation \
  -H "Content-Type: application/json" \
  -d "{
    \"TransactionType\": \"Pay Bill\",
    \"TransID\": \"$TRANS_ID\",
    \"TransTime\": \"$(date +%Y%m%d%H%M%S)\",
    \"TransAmount\": \"2500\",
    \"BusinessShortCode\": \"<your channel's shortcode>\",
    \"BillRefNumber\": \"<a tenant's payment_account_code>\",
    \"MSISDN\": \"254712345678\",
    \"FirstName\": \"Jane\"
  }"
```

Always returns `{"ResultCode":0,"ResultDesc":"Accepted"}` (Safaricom's contract - this
endpoint can never undo money that already moved, so it always acknowledges receipt).
**Check the actual row in C2B Payments**, not just this response, to see whether it
matched. Try it three ways to exercise the whole cascade: a real `BillRefNumber` (auto-
matches), garbage `BillRefNumber` + a real tenant's `MSISDN` (matches via phone), and
both garbage (lands as needs_review).

## Scoping to a property or your whole portfolio

One landlord can have several M-Pesa Channels. `Applies to` decides which tenants use
which channel:

- **Leave it blank** - this becomes the landlord's default channel, used by any
  property that doesn't have a more specific one of its own.
- **Pick a specific property** - only that property's tenants use this channel; other
  properties fall back to the landlord's default (if one exists).

So: one Paybill for everything -> one channel, blank. Several properties each with
their own Paybill -> one channel per property. A mix -> one blank default channel plus
one more channel scoped to just the property that's different. The more specific
channel always wins for its property - you never have to touch the shared one when
adding an exception.

## Going live

1. Apply for a real Paybill or Till (via Safaricom directly, or your bank/SACCO if
   they issue one on your behalf).
2. In your Daraja app on the developer portal, go through Safaricom's **Go Live**
   process for that Paybill/Till - this issues you **production** Consumer Key/Secret
   and Online Passkey, separate from your sandbox ones.
3. Confirm your site's `APP_URL` is a real, publicly reachable **HTTPS** domain (not
   `localhost`, not an ngrok tunnel long-term) - the app refuses to send a live STK
   push with a non-HTTPS callback URL.
4. Edit the channel: replace the Consumer Key/Secret/Passkey with the production
   values, replace the Paybill/Till number with the real one, toggle **Use Sandbox**
   **off**.
5. If using C2B, click **Register C2B** again - this re-registers your confirmation/
   validation URLs against Safaricom's live environment for that shortcode (sandbox
   registration doesn't carry over).
6. Do one real test payment for a small amount (e.g. KES 1) from an actual phone
   before telling tenants to start paying - for STK, via Pay Now; for C2B, by actually
   paying the Paybill.
7. Only once that succeeds, roll it out to tenants generally.

## Reference - exact endpoints this app uses

| Purpose | URL |
|---|---|
| OAuth token (sandbox) | `https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials` |
| OAuth token (live) | `https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials` |
| STK push (sandbox) | `https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest` |
| STK push (live) | `https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest` |
| STK push query (sandbox) | `https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query` |
| STK push query (live) | `https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query` |
| C2B register URL (sandbox) | `https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl` |
| C2B register URL (live) | `https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl` |

> **Correction from an older doc**: sandbox and live use **different** base hosts
> (`sandbox.safaricom.co.ke` vs `api.safaricom.co.ke`) - an earlier version of this
> documentation incorrectly claimed both used `api.safaricom.co.ke`.

Webhook URLs Safaricom calls **into** this app (all under your site's `APP_URL`):

| Purpose | Path | Registered how |
|---|---|---|
| STK push result | `/api/mpesa/callback` | Sent automatically inside every STK push request - nothing to register manually |
| C2B confirmation | `/api/mpesa/c2b/confirmation` | Registered once per shortcode via the **Register C2B** button |
| C2B validation | `/api/mpesa/c2b/validation` | Registered at the same time, same button |

## Troubleshooting

**"Failed to obtain access token" / OAuth failure**
Wrong Consumer Key or Secret for that shortcode - re-copy them from the Daraja app tied
to that exact Paybill/Till, watching for stray whitespace.

**STK push fails immediately with an invalid-shortcode-style error**
The Business Short Code doesn't match the Daraja app the Consumer Key/Secret came
from, or you're using a live shortcode with `Use Sandbox` still on (or vice versa).

**STK prompt never arrives / times out**
Usually a phone number formatting issue outside sandbox (must resolve to
`2547XXXXXXXX`), or, in sandbox, using a real phone number instead of
`254708374149`.

**"Register C2B" button doesn't appear**
C2B isn't enabled for this landlord yet - Superadmin > Landlords > edit > toggle it on
first.

**C2B registration fails**
Wrong credentials for that shortcode, or the shortcode is a Till rather than a
Paybill (Tills can't do C2B account-number matching).

**C2B payments show up but always land as "needs_review"**
Either the tenant wasn't told their `payment_account_code`, or their phone number on
file doesn't match the number they're actually paying from (e.g. a spouse's phone).
Use the **Assign to tenant** action on the C2B Payments row to reconcile it manually -
this is expected to happen occasionally, not a bug.

**Nothing arrives at all, not even as "needs_review"**
Either C2B was never registered for that shortcode (Step 4.3), or the confirmation URL
isn't reachable from the internet yet (still on `localhost` with no tunnel) -
Safaricom can't call a URL it can't resolve. Confirm `APP_URL` is a real, reachable
HTTPS URL before registering in live mode.

## Further reading

- [Safaricom Daraja API docs](https://developer.safaricom.co.ke/)
- [Lipa Na M-Pesa Online (STK Push) guide](https://developer.safaricom.co.ke/docs?java#lipa-na-m-pesa-online)
- [Customer To Business (C2B) guide](https://developer.safaricom.co.ke/APIs/CustomerToPayBillPayment)
