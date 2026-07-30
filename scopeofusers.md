# Renty — Scope of Access by User Role

Renty has five roles: **superadmin**, **landlord**, **admin**, **caretaker**, and **tenant**.
Every role now signs in through the same `/login` page and lands in a mobile-first
"app" experience (bottom tab bar on phones, sidebar on desktop) built specifically
for that role. The older Filament admin-panel screens (`/admin`, `/tenant`,
`/superadmin`) still exist underneath and are fully functional — nothing was
removed — but they are no longer the primary thing people see day to day.

Data isolation between landlords is automatic and enforced at the database-query
level (a global scope keyed to the signed-in user's `landlord_id`), so everything
described below about "what a role sees" already assumes it's scoped to *that
person's own landlord's* data — no role can see another landlord's tenants,
invoices, or payments regardless of what screen they're on.

---

## Superadmin

**Who this is:** the platform operator (you). Not tied to any single landlord.

**What they access:**
- **Dashboard** — total landlords on the platform, active vs. trialing subscription counts, and an estimated MRR (sum of active subscriptions' package prices).
- **Landlords** — every landlord account on the platform, their status (active/suspended), and their current subscription/package. Each landlord has a "Manage settings" link that opens the *full* settings screen for that landlord — SMS templates, email/SMTP, M-Pesa & Pesapal credentials, payment mode, everything — so the superadmin can configure or troubleshoot a landlord's account without needing that landlord's password.
- **Packages** — the pricing plans sold on the marketing site (Starter/Growth/Pro), each with its property/unit/tenant limits and active/inactive state.
- **Subscriptions** — every landlord's subscription record across the whole platform: status (trialing/active/past_due/expired/cancelled), trial end date or expiry date. This is the place to see who's about to lose trial access or fall past due.
- **Platform Settings** — the Google Analytics measurement ID and the public support email shown on the marketing site. This is platform-wide, not per-landlord.

**How this helps them manage the site:** the superadmin is the only role that can see *across* landlords at all. This is where you monitor platform health (how many customers, who's paying, who's about to churn), configure a landlord's account on their behalf when they need help, and manage the sellable packages/pricing without touching code.

---

## Landlord

**Who this is:** the property owner. Created automatically via the self-serve signup flow, starts on a trial subscription. Full control over their own portfolio and staff.

**What they access** (everything below is scoped to their own landlord automatically):
- **Dashboard** — occupancy rate, this month's revenue, outstanding balance across all tenants, total tenant count, and a feed of recent payments.
- **Tenants** — every tenant across every property they own. Can **admit a new tenant**: this one action creates a login account for the tenant, texts them their temporary password, assigns them to a vacant house, and flips that house to Occupied — all in one step.
- **Properties** — every location and every house/unit in it, with rent and occupancy status at a glance.
- **Invoices** — every invoice, filterable by paid/partial/unpaid.
- **Payments** — full payment history, and can **record a manual payment** (cash, bank transfer, or a manually-entered M-Pesa reference) against any tenant's outstanding invoice — this immediately recalculates that invoice's balance and status and updates the tenant's running balance.
- **Bills** — utility bills (water/electricity/internet/trash) per tenant per month; these get automatically folded into that tenant's next invoice.
- **Issues** — every maintenance issue tenants have reported, with the ability to move each one between Open → In Progress → Resolved.
- **Notices (to vacate)** — every notice a tenant has submitted, with Approve/Deny actions.
- **Reports** — a 6-month revenue trend and a paid/partial/unpaid breakdown across all invoices (a "view full report suite" link opens the deeper Filament reports for more detailed analysis).
- **Staff** — create and view **admin** and **caretaker** accounts for their own team. (Landlord and superadmin accounts can never be created from here — only via signup or the superadmin panel — so staff can't grant themselves higher access.)
- **Settings** — the payment collection mode (Manual vs. Automatic/M-Pesa+Pesapal), with a link out to the fuller settings screen for SMS/email templates and gateway credentials.
- **Chat** — direct messaging with any of their tenants or staff.

**How this helps them manage the site:** this is the full "run my rental business" view — admit tenants, bill them, collect and record rent, handle maintenance and move-out requests, and see the financial health of the whole portfolio, without ever touching the underlying admin-panel tooling.

**What's different from admin:** landlords can add staff and change landlord-wide settings; admins can too (they have the same access level day-to-day) — the real distinction is that only the landlord's own account was the one created at signup, and only landlord/superadmin can ever be assigned as a role (an admin creating a new staff account can only make more admins or caretakers).

---

## Admin

**Who this is:** trusted staff hired by a landlord to help run the business — same day-to-day capability as the landlord, minus the ability to be the "original" account.

**What they access:** identical to the landlord's list above — Dashboard, Tenants, Properties, Invoices, Payments, Bills, Issues, Notices, Reports, Staff, Settings, Chat — all scoped to whichever landlord they work for.

**How this helps them manage the site:** lets a landlord delegate full day-to-day operations (admitting tenants, recording payments, resolving issues) to office staff without handing over the actual landlord account.

---

## Caretaker

**Who this is:** on-site staff responsible for a **single property location** (e.g. "the caretaker for Kilimani Heights"). Assigned to exactly one location when their account is created.

**What they access — narrowed to only their assigned location:**
- **Dashboard, Tenants, Properties, Invoices, Payments, Bills, Issues, Notices, Chat** — all present, but every list only ever shows tenants/houses/invoices/etc. belonging to houses in their one assigned location. A caretaker for Kilimani Heights cannot see or act on anything at Westlands Vista, even though both belong to the same landlord.
- Can still admit tenants, record bills and payments, and manage issues/notices — the full operational toolkit — but only for their own building.

**What they're blocked from (returns a clear "not authorized" rather than silently hiding data):**
- **Reports** — cross-property financial reporting isn't relevant to a single-location caretaker.
- **Staff** — caretakers don't manage who else works for the landlord.
- **Settings** — SMS/email templates, payment gateway credentials, and payment mode are landlord-wide decisions, not something a single-location caretaker should change.

**How this helps them manage the site:** gives on-site staff exactly the tools they need to run their one building day to day — admitting tenants, logging bills, recording rent paid in person, handling maintenance requests — while keeping them from seeing (or accidentally changing) anything outside their remit or affecting the landlord's other properties.

---

## Tenant

**Who this is:** the renter. Gets a login automatically when a landlord/admin/caretaker admits them, with credentials sent by SMS.

**What they access — always scoped to just their own tenancy:**
- **Home (dashboard)** — their house, monthly rent, pending balance, and their most recent invoices and payments at a glance.
- **Invoices** — every invoice issued to them, with a **Pay Now** button when their landlord has switched on Automatic payment mode (lets them pay via M-Pesa STK push or Pesapal directly from the invoice; the button is hidden entirely for landlords still on Manual mode, since there's nothing to pay in-app in that case).
- **Bills** — the utility charges (water/electricity/internet/trash) that feed into their invoices each month, broken down by month.
- **Payments** — their full payment history.
- **Issues** — can report a maintenance problem (title + description) and track it through Open → In Progress → Resolved.
- **Notice to Vacate** — can submit a move-out notice (date, reason) and see whether the landlord has approved, denied, or is still reviewing it, including any note the landlord leaves.
- **Chat** — direct messaging with their landlord/admin/caretaker.
- **Profile** — update their own name, email, phone, and password.

**How this helps them manage the site:** gives tenants a self-service way to see exactly what they owe and have paid, pay rent instantly when the landlord supports it, report problems, and give notice — without ever needing to call or message the landlord for routine things, and without ever being able to see anything belonging to another tenant.
