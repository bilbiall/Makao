<div class="space-y-4">
    @if (session('tenant-admitted'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('tenant-admitted') }}
        </div>
    @endif

    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Admit a tenant
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Full name</label>
                <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Email</label>
                <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Phone number</label>
                <input type="text" wire:model="phone_number" placeholder="0712345678" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('phone_number') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">House (vacant only)</label>
                <select wire:model="house_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Select a house</option>
                    @foreach ($vacantHouses as $house)
                        <option value="{{ $house->id }}">{{ $house->house_name }} - KES {{ number_format($house->rent_amount) }}/mo</option>
                    @endforeach
                </select>
                @error('house_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Date admitted</label>
                <input type="date" wire:model="date_admitted" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">A login account is created automatically and the temporary password is sent to the tenant by SMS.</p>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="admit" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Admit tenant</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($tenants as $tenant)
            <button type="button" wire:click="viewTenant({{ $tenant->id }})"
                class="w-full text-left rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between hover:border-emerald-300 hover:bg-emerald-50/40 transition dark:bg-slate-900 dark:border-slate-800 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/10">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $tenant->tenant_name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $tenant->house?->house_name ?? 'No house' }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $tenant->phone_number }}</p>
                </div>
                <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                    Admitted<br>{{ \Carbon\Carbon::parse($tenant->date_admitted)->format('d M Y') }}
                </div>
            </button>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No tenants yet.
            </div>
        @endforelse

        <div>{{ $tenants->links() }}</div>
    </div>

    <!-- Tenant history & payments popup -->
    @if ($this->selectedTenant)
        @php $t = $this->selectedTenant; @endphp
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40" wire:click="closeTenantModal"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
                <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-lg max-h-[85vh] sm:max-h-[80vh] overflow-y-auto shadow-xl dark:bg-slate-900">
                    <div class="sticky top-0 bg-white border-b border-slate-200 p-4 flex items-start justify-between dark:bg-slate-900 dark:border-slate-800">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $t->tenant_name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $t->house?->house_name ?? 'No house' }} &middot; {{ $t->phone_number }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">Admitted {{ \Carbon\Carbon::parse($t->date_admitted)->format('d M Y') }}</p>
                        </div>
                        <button type="button" wire:click="closeTenantModal" class="p-1 text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-300 flex-shrink-0" aria-label="Close">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </button>
                    </div>

                    <div class="p-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Balance</p>
                            <p class="text-lg font-semibold {{ $t->balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400' }}">KES {{ number_format($t->balance) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Rent</p>
                            <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">KES {{ number_format($t->house?->rent_amount ?? 0) }}</p>
                        </div>
                    </div>

                    <div class="px-4 pb-2">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Invoice history</p>
                        <div class="space-y-2">
                            @forelse ($t->invoices as $invoice)
                                <div class="flex items-center justify-between rounded-lg border border-slate-100 dark:border-slate-800 px-3 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $invoice->invoice_number }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">KES {{ number_format($invoice->amount) }}</p>
                                        <p @class([
                                            'text-xs font-medium',
                                            'text-emerald-600 dark:text-emerald-400' => $invoice->status === 'paid',
                                            'text-amber-600 dark:text-amber-400' => $invoice->status === 'partial',
                                            'text-rose-600 dark:text-rose-400' => $invoice->status === 'unpaid',
                                        ])>{{ ucfirst($invoice->status) }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 dark:text-slate-500 py-2">No invoices yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="px-4 pb-4">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Payment history</p>
                        <div class="space-y-2">
                            @forelse ($t->payments as $payment)
                                <div class="flex items-center justify-between rounded-lg border border-slate-100 dark:border-slate-800 px-3 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $payment->payment_method ?? 'Payment' }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '' }}{{ $payment->payment_reference ? ' · ' . $payment->payment_reference : '' }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($payment->amount_paid) }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 dark:text-slate-500 py-2">No payments yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
