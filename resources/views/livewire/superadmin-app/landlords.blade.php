@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-3">
    @if (session('landlord-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('landlord-saved') }}
        </div>
    @endif
    @if (session('landlord-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('landlord-error') }}
        </div>
    @endif

    @forelse ($landlords as $landlord)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $landlord->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $landlord->contact_email }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $landlord->status === 'active',
                    'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' => $landlord->status === 'suspended',
                ])>{{ ucfirst($landlord->status) }}</span>
            </div>
            @if ($landlord->currentSubscription)
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ $landlord->currentSubscription->package?->name }} &middot;
                    <span class="capitalize">{{ $landlord->currentSubscription->status }}</span>
                </p>
            @endif
            <div class="mt-2">
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $landlord->verification_status === 'verified',
                    'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $landlord->verification_status === 'pending',
                    'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' => $landlord->verification_status === 'rejected',
                    'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => in_array($landlord->verification_status, ['unverified', null], true),
                ])>{{ ucfirst($landlord->verification_status ?? 'unverified') }}</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button type="button" wire:click="startEdit({{ $landlord->id }})" class="rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                    Edit
                </button>
                <a href="{{ route('app.superadmin.landlord-settings', $landlord) }}" class="block text-center rounded-lg border border-slate-300 py-2 text-xs font-medium text-slate-700 dark:border-slate-700 dark:text-slate-300">
                    Manage settings
                </a>
            </div>
            @if (in_array($landlord->verification_status, ['pending', 'rejected'], true))
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <button type="button" wire:click="approveVerification({{ $landlord->id }})" wire:confirm="Mark {{ $landlord->name }} as verified?" class="rounded-lg bg-emerald-600 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                        Approve verification
                    </button>
                    <button type="button" wire:click="startReject({{ $landlord->id }})" class="rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                        Reject
                    </button>
                </div>
            @elseif ($landlord->verification_status === 'verified')
                <button type="button" wire:click="startReject({{ $landlord->id }})" class="mt-2 w-full rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                    Revoke verification
                </button>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No landlords yet.
        </div>
    @endforelse

    <div>{{ $landlords->links() }}</div>

    {{-- Edit modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40" wire:click="cancelForm"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
                <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-lg max-h-[85vh] sm:max-h-[80vh] overflow-y-auto shadow-xl dark:bg-slate-900 p-4 space-y-4">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">Edit landlord</p>

                    <div class="space-y-3">
                        <div>
                            <label class="{{ $labelClass }}">Business name</label>
                            <input type="text" wire:model="name" class="{{ $inputClass }}">
                            @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Contact email</label>
                            <input type="email" wire:model="contact_email" class="{{ $inputClass }}">
                            @error('contact_email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">Phone number</label>
                                <input type="text" wire:model="phone_number" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Status</label>
                                <select wire:model="status" class="{{ $inputClass }}">
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" wire:model="c2b_enabled" class="rounded border-slate-300 dark:border-slate-700">
                            C2B (Paybill) reconciliation enabled
                        </label>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Owner login</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 -mt-2">The account this business owner signs in with - separate from the business details above.</p>

                        <div>
                            <label class="{{ $labelClass }}">Owner name</label>
                            <input type="text" wire:model="owner_name" class="{{ $inputClass }}">
                            @error('owner_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Owner login email</label>
                            <input type="email" wire:model="owner_email" class="{{ $inputClass }}">
                            @error('owner_email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Reset password</label>
                            <input type="password" wire:model="owner_password" placeholder="Leave blank to keep current password" class="{{ $inputClass }}">
                            @error('owner_password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="cancelForm" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                        <button type="button" wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reject/revoke verification modal --}}
    @if ($decidingVerificationId)
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40" wire:click="cancelReject"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
                <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md p-4 shadow-xl dark:bg-slate-900">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">Reject / revoke verification</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">This reason is shown to the landlord.</p>
                    <textarea wire:model="rejectionNotes" rows="3" placeholder="Reason (optional)"
                        class="mt-3 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder:text-slate-400"></textarea>
                    <div class="mt-3 flex gap-2">
                        <button type="button" wire:click="cancelReject" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium py-2">Cancel</button>
                        <button type="button" wire:click="confirmReject" class="flex-1 rounded-lg bg-rose-600 text-white text-sm font-semibold py-2">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
