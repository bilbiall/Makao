<x-filament::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 shadow rounded p-4 space-y-3">
            <h3 class="font-semibold dark:text-gray-100">Bring in your existing data</h3>
            <p class="text-sm text-gray-500 dark:text-gray-300">
                Moving from a spreadsheet or another system? Download a template below, fill it
                in, and upload it back - no need to add everything by hand.
            </p>

            <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <li>Click an import button below. Use "Download example CSV" in the dialog to get the template with the exact column headings.</li>
                <li>Fill in one row per record and save it as CSV, then upload it back through the same dialog.</li>
                <li>Do them in order: <strong>Properties</strong> &rarr; <strong>Units</strong> &rarr; <strong>Tenants</strong> &rarr; <strong>Invoices</strong> &rarr; <strong>Payments</strong>. Each step looks up the ones before it by name/phone number, so a Tenant row needs its Unit to already exist, and so on.</li>
                <li><strong>Expenses</strong> is independent and can be imported any time.</li>
            </ol>

            <p class="text-sm text-gray-500 dark:text-gray-300">
                Imported tenants, invoices and payments won't trigger welcome SMS or payment
                notifications - those only fire for new activity going forward.
            </p>
        </div>
    </div>
</x-filament::page>
