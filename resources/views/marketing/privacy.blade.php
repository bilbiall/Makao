<x-layouts.marketing :title="'Privacy Policy'">
    <section class="max-w-3xl mx-auto px-6 py-20 text-slate-700 leading-relaxed space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Privacy Policy</h1>
            <p class="text-sm text-slate-400 mt-1">Last updated: {{ now()->format('F Y') }}</p>
        </div>

        <p>
            Renty ("we", "us") provides rental management software to landlords and property managers
            ("Landlords") and their tenants ("Tenants"). This policy explains what information we collect
            and how it is used.
        </p>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Information we collect</h2>
            <p>
                Account details (name, email, phone number), property and tenancy records entered by Landlords,
                and payment references from M-Pesa/Pesapal transactions initiated through the platform. We do not
                store full card or M-Pesa credentials - payments are processed by the respective payment provider.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">How we use it</h2>
            <p>
                To operate the service (invoicing, payment reconciliation, notifications), to communicate with
                you about your account, and to improve the product. Landlord data is never visible to other
                Landlords on the platform.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">SMS and email</h2>
            <p>
                With a Landlord's configuration, we send SMS and email notifications on their behalf (invoices,
                payment confirmations, account creation) to their Tenants and staff.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Data retention</h2>
            <p>
                Landlords control retention of their own tenant/property records. Vacated tenant records are
                archived and automatically purged after a configurable period.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Contact</h2>
            <p>For privacy questions, contact your account administrator or reach out via the details on our homepage.</p>
        </div>
    </section>
</x-layouts.marketing>
