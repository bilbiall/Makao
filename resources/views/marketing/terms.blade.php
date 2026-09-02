<x-layouts.marketing :title="'Terms of Service'">
    <section class="max-w-3xl mx-auto px-6 py-20 text-slate-700 leading-relaxed space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Terms of Service</h1>
            <p class="text-sm text-slate-400 mt-1">Last updated: {{ now()->format('F Y') }}</p>
        </div>

        <p>These terms govern your use of Renty. By creating an account, you agree to them.</p>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Accounts</h2>
            <p>
                Property Owners are responsible for the accuracy of the property, tenant, and financial data they enter,
                and for managing access of the staff accounts they create.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Subscriptions</h2>
            <p>
                New accounts start with a free trial. After the trial period, continued access requires an active
                subscription. Subscription payments are currently arranged directly with our team (M-Pesa or bank
                transfer) - no card is charged automatically.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Acceptable use</h2>
            <p>
                The platform may only be used for legitimate rental property management. Sending unsolicited bulk
                messages unrelated to a tenancy, or attempting to access another Property Owner's data, is prohibited.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Payments processed on your behalf</h2>
            <p>
                M-Pesa and Pesapal payments initiated by Tenants are processed by those providers directly; Renty
                records the resulting transaction against the relevant invoice.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">Termination</h2>
            <p>
                We may suspend an account for violation of these terms. Property Owners may request account closure
                at any time.
            </p>
        </div>
    </section>
</x-layouts.marketing>
