@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
@endphp
<div class="space-y-4">
    @if (session('announcement-sent'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('announcement-sent') }}
        </div>
    @endif

    @if (!$showForm)
        <button type="button" wire:click="startCreate" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
            + New announcement
        </button>
    @else
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">New announcement</p>

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Subject</label>
                <input type="text" wire:model="subject" placeholder="e.g. Water shutdown this Friday" class="{{ $inputClass }}">
                <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Used as the email subject line.</p>
                @error('subject') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Message</label>
                <textarea wire:model="message" rows="4" class="{{ $inputClass }}"></textarea>
                <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Used as both the SMS text and the email body.</p>
                @error('message') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Send to</label>
                <select wire:model="location_id" class="{{ $inputClass }}">
                    <option value="">All my properties</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" wire:model="send_sms" class="rounded border-slate-300 dark:border-slate-700">
                    Send via SMS
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" wire:model="send_email" class="rounded border-slate-300 dark:border-slate-700">
                    Send via Email
                </label>
            </div>
            @error('send_sms') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

            <div class="flex gap-3">
                <button type="button" wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button type="button" wire:click="send" wire:loading.attr="disabled" wire:target="send" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Send</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($announcements as $announcement)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $announcement->subject }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $announcement->location?->location_name ?? 'All properties' }}
                            &middot; {{ optional($announcement->sent_at)->format('d M Y, H:i') }}
                        </p>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        @if ($announcement->send_sms)
                            <span class="rounded-full bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400 px-2.5 py-0.5 text-[11px] font-medium">SMS</span>
                        @endif
                        @if ($announcement->send_email)
                            <span class="rounded-full bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 px-2.5 py-0.5 text-[11px] font-medium">Email</span>
                        @endif
                    </div>
                </div>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $announcement->message }}</p>
                <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                    {{ $announcement->recipients_total }} recipient{{ $announcement->recipients_total === 1 ? '' : 's' }}
                    @if ($announcement->send_sms)
                        &middot; SMS {{ $announcement->sms_sent }} sent{{ $announcement->sms_failed ? ", {$announcement->sms_failed} failed" : '' }}
                    @endif
                    @if ($announcement->send_email)
                        &middot; Email {{ $announcement->email_sent }} sent{{ $announcement->email_failed ? ", {$announcement->email_failed} failed" : '' }}
                    @endif
                </p>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No announcements sent yet.
            </div>
        @endforelse

        <div>{{ $announcements->links() }}</div>
    </div>
</div>
