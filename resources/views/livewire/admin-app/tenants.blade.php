<div class="space-y-4">
    @if (session('tenant-admitted'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('tenant-admitted') }}
        </div>
    @endif

    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Admit a tenant
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3">
            <div>
                <label class="text-xs font-medium text-slate-600">Full name</label>
                <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Email</label>
                <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Phone number</label>
                <input type="text" wire:model="phone_number" placeholder="0712345678" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('phone_number') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">House (vacant only)</label>
                <select wire:model="house_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select a house</option>
                    @foreach ($vacantHouses as $house)
                        <option value="{{ $house->id }}">{{ $house->house_name }} - KES {{ number_format($house->rent_amount) }}/mo</option>
                    @endforeach
                </select>
                @error('house_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Date admitted</label>
                <input type="date" wire:model="date_admitted" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <p class="text-xs text-slate-500">A login account is created automatically and the temporary password is sent to the tenant by SMS.</p>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-medium text-slate-700">Cancel</button>
                <button wire:click="admit" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Admit tenant</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($tenants as $tenant)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $tenant->tenant_name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $tenant->house?->house_name ?? 'No house' }}</p>
                    <p class="text-xs text-slate-400">{{ $tenant->phone_number }}</p>
                </div>
                <div class="text-right text-xs text-slate-500">
                    Admitted<br>{{ \Carbon\Carbon::parse($tenant->date_admitted)->format('d M Y') }}
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
                No tenants yet.
            </div>
        @endforelse

        <div>{{ $tenants->links() }}</div>
    </div>
</div>
