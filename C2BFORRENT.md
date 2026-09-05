# M-Pesa C2B (Paybill) Reconciliation

This document covers the C2B (Customer-to-Business) integration - automatically
detecting and crediting rent paid directly to a Paybill from a tenant's own M-Pesa
app, without them ever clicking "Pay Now" on the site. It's a separate flow from the
STK Push integration (`MPESA_INTEGRATION.md`) - read that first if you haven't, since
C2B channels build on the same Daraja credentials concept.

> **Founder-gated:** C2B is off for every landlord by default. A landlord can create
> M-Pesa Channels and use STK push immediately, but the "Register C2B" button only
> appears once **you** turn it on for them (Superadmin > Landlords > edit > "C2B
> (Paybill) reconciliation enabled"). Misrouted/unreconciled real cash is a
> higher-stakes failure mode than a rejected STK push, so review a landlord before
> flipping this on.

> **You can now set up a client's credentials yourself, from Superadmin.** You don't
> have to wait for a landlord/property manager to create their own M-Pesa Channel.
> Superadmin > Landlords > row > **M-Pesa Channels** (or the **M-Pesa Channels** tab on
> a landlord's **Settings** page) opens a founder-facing version of the same
> Create/Edit form the landlord sees in their own Admin panel - same fields (Paybill,
> Daraja Consumer Key/Secret, Passkey, Sandbox toggle), plus the same **Register C2B**
> button, except you pick which landlord it belongs to. This is the easiest way to test
> a new client's Daraja app end-to-end before handing the account over to them, or to
> set it up for a client who isn't comfortable doing it themselves. See "Testing
> per-client" below.

## Overview

1. A tenant pays Paybill directly from their M-Pesa app - Paybill number, Account
   Number, amount - with no interaction with the site at all.
2. Safaricom POSTs a **Confirmation** to `/api/mpesa/c2b/confirmation` (registered
   once per shortcode via the "Register C2B" button).
3. The system looks up which landlord/property owns that shortcode (`MpesaChannel`),
   then tries to identify the tenant:
   - **Account Number** typed by the payer, matched against each tenant's
     `payment_account_code` (defaults to their unit name, e.g. "B4").
   - If that doesn't match, the payer's **phone number**, matched against the
     tenant's `phone_number` on file.
   - If neither matches confidently, the payment is left as **needs_review** - it is
     never guessed from the amount alone.
4. A confident match creates a `Payment` record exactly the way an STK push payment
   does - invoice balance, tenant balance, SMS receipt, admin notification, all
   automatic, all reused from the existing Payment model.
5. Every payment - matched or not - lands in **Admin > M-Pesa Channels >
   C2B Payments**, so nothing silently disappears.

## Setup

### 1. Enable C2B for the landlord (you, the founder)

1. Log in as superadmin.
2. Go to **Superadmin > Landlords**, edit the landlord.
3. Toggle **C2B (Paybill) reconciliation enabled** on. Save.

Until you do this, the landlord can still create M-Pesa Channels and use STK push,
but they won't see a way to register C2B.

### 2. Create an M-Pesa Channel

Either the landlord does this themselves, or you do it for them from Superadmin -
same form either way:

**Landlord/admin, in their own panel:**
1. Log in as the landlord/admin.
2. Go to **Admin > M-Pesa Channels > Create**.

**You, from Superadmin (no need to log in as them):**
1. Log in as superadmin.
2. Go to **Superadmin > Landlords**, click **M-Pesa Channels** on that landlord's row
   (this takes you straight to a list already filtered to them) and click **Create**,
   or use the **Add M-Pesa Channel** button on the **M-Pesa Channels** tab of their
   **Settings** page.
3. Pick the **Landlord** first (pre-filled if you arrived via one of the links above)
   - this also narrows the **Applies to** property list to that landlord's own
     properties.

Either way, fill in:
   - **Label** - anything memorable, e.g. "Kilimani Apartments Paybill".
   - **Applies to** - pick a specific property, or leave blank to make this the
     landlord's default channel (see "Going live" scenarios below).
   - **Paybill / Till Number** - must be a real **Paybill**, not a Till. Tills have
     no Account Number field, so there's nothing for C2B to match on.
   - **Daraja Consumer Key / Secret** and **Online Passkey** - from
     [developer.safaricom.co.ke](https://developer.safaricom.co.ke), same as the STK
     push setup in `MPESA_INTEGRATION.md`. Each Daraja app has its own sandbox
     credentials, separate from its live ones - see "Testing per-client" below for
     where to get sandbox-only credentials that work for any client without them
     needing their own Daraja account yet.
   - **Use Sandbox** - ON for testing, OFF only once you're ready to go live.
4. Save.

### 3. Register C2B with Safaricom

1. Open the channel you just created (it now has an Edit page with a header button).
2. Click **Register C2B**. This calls Daraja's `registerurl` API, telling Safaricom
   to start sending Paybill confirmations to this app for that shortcode.
3. On success, the channel shows "Registered with Safaricom on <date>". On failure,
   the notification shows Safaricom's error - usually a bad consumer key/secret or
   an invalid shortcode.

> In Daraja **sandbox**, registration and confirmations work against Safaricom's
> test Paybill (`600xxx`-style shortcodes they issue you), not a real one - see
> Testing below. In **live**, this is a real business Paybill and this step tells
> Safaricom to actually start forwarding real payment confirmations here - do this
> once you're confident the sandbox test works.

### 4. Give each tenant an Account Number

Every tenant has a `payment_account_code` field (**Admin > Tenants > edit**, labeled
"M-Pesa Account Number"). It defaults to their unit name at creation (something they
already know without being told anything new) and is what gets matched against the
Account Number a payer types in. Tell tenants explicitly what to enter when they pay
Paybill - it should match this field exactly (matching is case-insensitive).

If a landlord doesn't bother customizing this, matching still works via phone number
as the fallback - see Overview above.

## Testing per-client (multiple landlords, one at a time)

You'll typically be validating C2B for one specific client (landlord/property
manager) at a time - either before they go live, or when troubleshooting why their
payments aren't reconciling. Here's the full loop, done entirely from Superadmin,
without needing that client's own login:

1. **Get that client's Daraja sandbox credentials.** Every Daraja app (at
   [developer.safaricom.co.ke](https://developer.safaricom.co.ke) > My Apps) has its
   own Consumer Key/Secret and a **test credentials** page (under the app's "Lipa Na
   M-Pesa Sandbox" product) listing a sandbox shortcode (a `6xxxxx`-style number) and
   passkey - these only work in sandbox, never live. If the client already has a
   Daraja account, ask them for these (or have them share screen); if not, you can
   create a throwaway Daraja app yourself just to test the flow end-to-end before
   they have their own.
2. **Create the channel for them** - Superadmin > Landlords > their row >
   **M-Pesa Channels** > Create (see Setup step 2 above). Landlord is pre-selected.
   Paste in the sandbox Consumer Key/Secret, the sandbox shortcode as
   **Paybill / Till Number**, leave **Use Sandbox** ON.
   - **Shortcodes must be globally unique** (`business_shortcode` has a unique
     constraint across every landlord's channels) - if you're testing more than one
     client, each needs its own distinct sandbox shortcode from their own Daraja app.
     Reusing the same shortcode for a second client will fail to save with a "has
     already been taken" error, which is expected - it's exactly what stops one
     client's confirmations from ever being routed to a different client's channel.
3. **Enable C2B for that landlord** if you haven't already (Setup step 1) and click
   **Register C2B** on the channel you just created.
4. **Give the test tenant an Account Number to test against** - pick (or temporarily
   create) one of that landlord's tenants and note their `payment_account_code`
   (**Admin > Tenants**, or query it directly if you're moving fast).
5. **Fire a test payment** using Option A below, with that channel's sandbox
   shortcode and that tenant's `payment_account_code`.
6. **Verify in that client's own C2B Payments dashboard** - not just the API
   response. Since you're acting as superadmin you won't see their **Admin > M-Pesa
   Channels > C2B Payments** list directly (it's scoped to their own landlord
   account) - either check the `mpesa_c2b_transactions` table for that
   `landlord_id`, or briefly impersonate/log in as that landlord/admin to see it the
   way they will.
7. Once sandbox proves out, repeat steps 2-3 with their **live** Daraja credentials
   (from their real, registered Paybill) and **Use Sandbox** OFF, then do one real
   small-amount test payment per "Going live" below before telling their tenants to
   start paying.

## Testing

### Option A: Safaricom's own sandbox simulator (closest to the real thing)

Daraja provides a C2B simulate endpoint that behaves like a real Paybill payment,
without needing an actual M-Pesa transaction:

```bash
curl -X POST https://sandbox.safaricom.co.ke/mpesa/c2b/v2/simulate \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "ShortCode": "<your sandbox shortcode>",
    "CommandID": "CustomerPayBillOnline",
    "Amount": "500",
    "Msisdn": "254708374149",
    "BillRefNumber": "<a tenant's payment_account_code>"
  }'
```

Get `<ACCESS_TOKEN>` the same way STK push does (Basic Auth with your channel's
consumer key/secret against `/oauth/v1/generate?grant_type=client_credentials`).
Safaricom will then call your registered Confirmation URL for real, exactly like a
live payment would - this is the most realistic pre-launch test.

### Option B: Call the local webhook directly (no Safaricom dependency)

Useful for testing the app's matching logic itself without needing sandbox network
access. Shape the payload the way Safaricom's Confirmation callback does:

```bash
TRANS_ID="TEST-$(date +%s)"

curl -X POST http://localhost/renty/public/api/mpesa/c2b/confirmation \
  -H "Content-Type: application/json" \
  -d "{
    \"TransactionType\": \"Pay Bill\",
    \"TransID\": \"$TRANS_ID\",
    \"TransTime\": \"$(date +%Y%m%d%H%M%S)\",
    \"TransAmount\": \"2500\",
    \"BusinessShortCode\": \"<your channel's business_shortcode>\",
    \"BillRefNumber\": \"<a tenant's payment_account_code>\",
    \"MSISDN\": \"254712345678\",
    \"FirstName\": \"Jane\"
  }"
```

Should return `{"ResultCode":0,"ResultDesc":"Accepted"}` regardless of outcome (per
Safaricom's contract - this endpoint can't undo money that already moved, so it
always acknowledges). Check what actually happened in **Admin > M-Pesa Channels >
C2B Payments** - the row's Status/Reason columns show whether it matched and why.

Try it three ways to exercise the whole matching cascade:
1. `BillRefNumber` = a real tenant's `payment_account_code` -> should auto-match and
   credit their invoice.
2. `BillRefNumber` = garbage, `MSISDN` = a real tenant's phone -> should auto-match
   via phone.
3. Both garbage -> should land as **needs_review**, and (per your notification
   preference) fire an admin notification.

Send the same `TransID` twice to confirm idempotency - only one `Payment`/balance
change should result either way.

### Sanity-check the dashboard, not just the API response

A `{"ResultCode":0,...}` response only means the request was received - always
verify the actual row in **C2B Payments** to see the real match outcome before
concluding a test passed.

## Going live - per-property scenarios

C2B (like STK) is scoped through `MpesaChannel`, which nullable-`location_id`
naturally supports every shape of business without different code paths:

### Scenario 1: One landlord, one Paybill for everything

Create a single M-Pesa Channel, leave **Applies to** blank. Every property's tenants
match against this one channel. This is the common case for a landlord with one or a
few units under one business.

### Scenario 2: A property manager with several distinct properties, each with its own Paybill

Create one M-Pesa Channel **per property**, picking that property under **Applies
to** each time. STK push and C2B for each property's tenants use that property's own
shortcode/credentials - a tenant at Property A never gets matched against Property
B's tenant list, and vice versa, since the channel itself already scopes which
tenants are even candidates.

### Scenario 3: Mixed - most properties share one Paybill, one property has its own

Create a default channel (blank **Applies to**) for the shared Paybill, plus one
more channel with **Applies to** set to the specific property that has its own.
`MpesaChannel::resolveFor()` always prefers the more specific (property-matched)
channel over the landlord's default, so this just works without needing to touch
the shared channel at all.

### Rolling out live, in order, for any of the above

1. Confirm the channel works correctly in **sandbox** first (Option A above, with
   Safaricom's own simulator) - don't skip straight to live credentials.
2. Get live Daraja credentials from Safaricom for that specific Paybill (a real
   registered business Paybill, not the sandbox test one).
3. Edit the channel: paste in the live consumer key/secret/passkey, toggle
   **Use Sandbox** off.
4. Click **Register C2B** again - this re-registers the URLs against Safaricom's
   live environment for that shortcode.
5. Do one real test payment for a small amount (e.g. KES 1) from an actual phone,
   using a real tenant's Account Number, and confirm it shows up correctly in
   **C2B Payments** and credits the right invoice.
6. Only then tell tenants to start paying that Paybill directly.

## Troubleshooting

### "Register C2B" button doesn't appear
- **Cause**: C2B isn't enabled for this landlord yet.
- **Fix**: Superadmin > Landlords > edit > toggle "C2B (Paybill) reconciliation
  enabled" on.

### Registration fails with an error about the shortcode or credentials
- **Cause**: Wrong consumer key/secret for that shortcode, or the shortcode is a
  Till rather than a Paybill.
- **Fix**: Double check the channel's credentials match the Daraja app tied to that
  exact Paybill number.

### Payments show up but always land as "needs_review"
- **Cause 1**: Tenants aren't being told their `payment_account_code`, or are typing
  something else.
- **Fix**: Check **Admin > Tenants** - make sure the field is filled in and
  communicated to the tenant (on their invoice, portal, or an SMS reminder).
- **Cause 2**: The tenant's phone number on file doesn't match the number they're
  actually paying from (e.g. paying from a spouse's phone).
- **Fix**: Use the **Assign to tenant** action on the C2B Payments row to manually
  reconcile it - this is expected to happen sometimes and isn't a bug.

### A payment matched a tenant but nothing was credited
- **Cause**: The tenant has no open (`unpaid`/`partial`) invoice - the row's
  `match_reason` will say so explicitly.
- **Fix**: This needs a human decision (create an invoice, treat as a prepayment,
  etc.) - by design, the system won't guess what to do with money that doesn't map
  to an existing bill.

### Nothing arrives at all, not even as "needs_review"
- **Cause 1**: C2B was never registered for that shortcode (see step 3 in Setup).
- **Cause 2**: The confirmation URL isn't reachable from the internet (e.g. still
  testing on `localhost` with no tunnel) - Safaricom can't reach a URL it can't
  resolve.
- **Fix**: Use a tunnel (ngrok or similar) for local testing, or test against a
  deployed environment; confirm `APP_URL` in `.env` is a real, reachable HTTPS URL
  before registering in live mode.

## File Structure

```
app/
  Models/
    MpesaChannel.php                 # Per-property (or landlord-default) M-Pesa credentials
    MpesaC2bTransaction.php          # One row per inbound Paybill confirmation
  Services/
    MpesaC2bMatchService.php         # The account-code -> phone -> needs_review cascade
    MpesaService.php                 # registerC2bUrls() + loadConfigForLocation()
  Http/
    Controllers/
      MpesaC2bController.php         # validation()/confirmation() webhook handlers
  Filament/
    Resources/
      MpesaChannelResource.php       # Landlord/admin's own channels, "Register C2B" action
      MpesaC2bTransactionResource.php# The C2B Payments dashboard
    Superadmin/
      Resources/
        MpesaChannelResource.php     # Same, but for any landlord - founder-facing, unscoped
        LandlordResource.php         # "C2B (Paybill) reconciliation enabled" toggle,
                                      #   "M-Pesa Channels" row action

routes/
  api.php                            # /api/mpesa/c2b/validation, /confirmation

database/
  migrations/
    2026_09_04_100000_create_mpesa_channels_table.php
    2026_09_04_100001_create_mpesa_c2b_transactions_table.php
    2026_09_04_100002_add_payment_account_code_to_tenants_table.php
    2026_09_04_100003_add_c2b_enabled_to_landlords_table.php
    2026_09_04_100004_add_mpesa_c2b_to_payments_payment_type_enum.php
```

## References

- [Safaricom Daraja C2B API Docs](https://developer.safaricom.co.ke/APIs/CustomerToPayBillPayment)
- [C2B Register URL](https://developer.safaricom.co.ke/Documentation) - `POST /mpesa/c2b/v1/registerurl`
- [C2B Simulate Transaction (sandbox only)](https://developer.safaricom.co.ke/Documentation) - `POST /mpesa/c2b/v2/simulate`
- `MPESA_INTEGRATION.md` - the STK push flow this builds on
