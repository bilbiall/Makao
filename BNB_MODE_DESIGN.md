# BNB (Short-Stay) Mode — Design Brainstorm

**Status: design-only.** Nothing in this document is implemented. It's a starting point for discussion before any migrations or code are written.

## Why this is a different problem

Renty's whole data model today is built around **long-term tenancy**: one `Tenant` occupies a `House` indefinitely, billing runs on a **recurring monthly cycle** (`SendAutoInvoices` generates one `Invoice` per tenant per calendar month), and the relationship ends via a `NoticeToVacate` workflow. Short-stay (BNB/Airbnb-style) breaks every one of those assumptions:

- A single unit can have **many short, non-overlapping stays** over time — occupancy is a *calendar*, not a status field.
- Billing is **per-stay** (nightly rate × nights, or a flat rate), not monthly.
- There's no "current tenant" — a guest checks in, checks out, and the unit resets.
- No monthly invoice cycle, no notice-to-vacate — a booking simply ends on its checkout date.

## Data model proposal

**Extend `House` with a `listing_mode` field, rather than building a separate `BnbListing` model.**

Add to `House`: `listing_mode` (enum: `long_term` default, or `short_term`), `nightly_rate` (nullable, only meaningful when `short_term`), optionally `min_stay_nights`/`max_stay_nights`.

Why extend rather than fork the model: a landlord converting a unit seasonally (e.g. a 1BR that's long-term most of the year but listed short-stay during a vacancy gap) is a real use case, and that's a much more natural fit for "flip a flag on the existing unit" than migrating data between two tables. `House` already carries the durable facts a listing needs regardless of mode (which `Location` it belongs to, its type, its identity) — duplicating that into a parallel model means every Location-level report (occupancy %, unit count) has to union two tables instead of querying one.

The existing `hasOne(Tenant::class)` relationship on `House` stays long-term-only and simply stays empty for `short_term` houses — short-stay occupancy lives in a new `bookings` table instead, not in `Tenant`.

**New table: `bookings`** — one row per stay:
- `house_id` (must be a `short_term` house)
- `guest_name`, `guest_phone`, `guest_email` (nullable) — **stored directly on the booking**, not as a `Tenant` or new `Guest` model. `Tenant`'s model hooks are too long-term-coupled to reuse safely (they flip house status on create/delete, assume a running `balance` that rolls invoice-to-invoice, and the whole app implicitly assumes one active `Tenant` per `House` at a time). Forcing many overlapping-in-time-but-not-in-dates `Tenant` rows per house for BNB guests would corrupt that assumption everywhere else in the app. A full `Guest` model is possible later (repeat-guest history/CRM) but is overkill for a first version.
- `check_in` (date), `check_out` (date)
- `nightly_rate` (a **snapshot** of `House.nightly_rate` at booking time — never recompute retroactively if the landlord later changes the listed rate)
- `nights`, `total_amount`
- `status`: `pending` | `confirmed` | `checked_in` | `checked_out` | `cancelled`
- `payment_status`: `unpaid` | `deposit_paid` | `paid` | `refunded`
- `notes`

Multi-tenancy note: this fits the landlord-scoping retrofit cleanly — `bookings` would get its own `landlord_id` column and the `BelongsToLandlord` trait, stamped from `house.landlord_id` the same way every other leaf table already works.

## Billing logic differences

- **No monthly invoice cron applies.** `SendAutoInvoices` (and the "Send Mass Invoices" admin action) should skip any house where `listing_mode = short_term` — in practice such houses won't have a long-term `Tenant` row at all, but an explicit guard (`whereHas('house', fn ($q) => $q->where('listing_mode', 'long_term'))`) makes the skip self-documenting rather than relying on the absence of a row.
- **Charge timing**: instead of a recurring monthly `Invoice`, a single charge is generated at **booking-confirmation time**, covering the full stay — or split as a deposit-at-confirmation + balance-due-before/at-check-in. Whether this reuses the existing `Invoice`/`Payment` models (with a nullable/polymorphic owner instead of `tenant_id`) or gets its own lightweight `BookingInvoice` is an open implementation decision — flagging it rather than resolving it here, since either has real trade-offs (reuse risks naming/reporting confusion between "SaaS subscription invoice," "tenant rent invoice," and "booking invoice"; a new model avoids that but duplicates some logic).
- **No `Bill` (utility) rollups** — short-stay Nairobi listings are near-universally all-inclusive (water/electricity/wifi bundled into the nightly rate), so `Bill` simply isn't invoked for `short_term` houses.
- **Payment collection**: same gateways (`MpesaService`, `PesapalController`), different trigger — collected at booking-confirmation/pre-check-in instead of a monthly due-date cadence. The controller/service layer is reusable as-is; only "what triggers a payment request" changes.

## The hard part (flagged, not designed)

**Overlap prevention** — making sure the same `House` can't be double-booked for intersecting date ranges — is the trickiest part of a real implementation: same-day turnover (one guest checks out the morning another checks in), timezone handling, and race conditions when two booking requests are confirmed near-simultaneously. This needs careful date-range-overlap query logic and likely a DB-level constraint or application-level locking around the confirm step. Don't underestimate this when it moves to implementation.

## UI/Filament implication

- A new `BookingResource`, scoped to houses where `listing_mode = short_term`.
- **v1**: a simple filterable table (house, guest, check-in, check-out, status, payment status) — achievable with stock Filament components, no extra dependency.
- **v2**: a calendar/timeline view is a nicer UX for spotting gaps/overlaps visually, but adds a third-party dependency and integration risk — worth deferring until the table-view v1 is validated with real usage.
- This sits alongside, not replacing, today's House/Tenant-centric navigation — BNB houses show "which bookings does this house have, past/current/upcoming," a genuinely different information shape than "which tenant lives here."

## Open questions for the next round

1. Does a booking-confirmation charge reuse `Invoice`/`Payment`, or get its own model?
2. Deposit-only vs. full-payment-upfront — configurable per landlord, or one fixed policy?
3. Does a landlord need to see combined occupancy/revenue reporting across long-term and short-term units in one view, or are they treated as separate "modes" in the dashboard?
