@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
    $branchColor = fn (?string $b) => match ($b) {
        'results', 'narrow', 'alternatives_shown' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
        'none', 'clarify', 'property_not_found' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
        'zero_results' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    };
@endphp
<div class="space-y-4">
    @if (session('chat-inbox-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('chat-inbox-saved') }}
        </div>
    @endif

    <p class="text-xs text-slate-500 dark:text-slate-400">What visitors wrote that the assistant couldn't answer. Tap <strong>Teach</strong> to add a nickname or Swahili phrase, or <strong>Ignore</strong> to clear it. Messages are stored masked and deleted after 90 days.</p>

    <div class="flex gap-2">
        @foreach (['attention' => "Needs attention ({$attentionCount})", 'all' => 'All messages'] as $key => $label)
            <button wire:click="$set('filter', '{{ $key }}')" @class([
                'rounded-full px-4 py-2 text-xs font-semibold',
                'bg-emerald-600 text-white' => $filter === $key,
                'bg-white border border-slate-200 text-slate-600 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300' => $filter !== $key,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search messages..." class="{{ $inputClass }}">

    <div class="space-y-2">
        @forelse ($turns as $turn)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-2 dark:bg-slate-900 dark:border-slate-800" wire:key="turn-{{ $turn->id }}">
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="rounded-full px-2 py-0.5 font-semibold {{ $branchColor($turn->branch) }}">{{ $turn->branch ?? 'n/a' }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $turn->language === 'sw' ? 'Swahili' : ($turn->language === 'en' ? 'English' : '-') }}</span>
                    @if ($turn->handoff_shown)<span class="rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">sent to team</span>@endif
                    @if ($turn->status)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $turn->status }}</span>@endif
                    <span class="ml-auto text-slate-400 dark:text-slate-500">{{ $turn->created_at->diffForHumans() }}</span>
                </div>

                <p class="text-sm font-medium text-slate-900 dark:text-slate-100 break-words">{{ $turn->user_text }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 whitespace-pre-line break-words">Bot: {{ \Illuminate\Support\Str::limit($turn->reply_text, 160) }}</p>

                <div class="flex flex-wrap gap-2 pt-1">
                    <button wire:click="startTeaching({{ $turn->id }})" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Teach</button>
                    <button wire:click="toggleThread({{ $turn->id }})" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 dark:border-slate-700 dark:text-slate-300">{{ $threadFor === $turn->id ? 'Hide conversation' : 'Conversation' }}</button>
                    @if ($turn->status === null)
                        <button wire:click="ignore({{ $turn->id }})" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Ignore</button>
                    @endif
                </div>

                @if ($teachingId === $turn->id)
                    <div class="space-y-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                        <div>
                            <label class="{{ $labelClass }}">This phrase…</label>
                            <select wire:model.live="teachType" class="{{ $inputClass }}">
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Phrase (trim to just the word to learn)</label>
                            <input type="text" wire:model="teachPhrase" class="{{ $inputClass }}">
                            @error('teachPhrase') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        @if (in_array($teachType, ['area', 'house_type'], true))
                            <div>
                                <label class="{{ $labelClass }}">Means</label>
                                <select wire:model="teachCanonical" class="{{ $inputClass }}">
                                    <option value="">Choose…</option>
                                    @foreach ($canonicalOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('teachCanonical') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <div>
                            <label class="{{ $labelClass }}">Language</label>
                            <select wire:model="teachLanguage" class="{{ $inputClass }}">
                                <option value="sw">Swahili / Sheng</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <div class="flex gap-3">
                            <button wire:click="cancelTeaching" class="flex-1 rounded-lg border border-slate-300 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-300">Cancel</button>
                            <button wire:click="saveTeaching" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save phrase</button>
                        </div>
                    </div>
                @endif

                @if ($threadFor === $turn->id)
                    <div class="space-y-2 rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800/60">
                        @foreach ($thread as $t)
                            <div @class(['rounded-lg p-2', 'bg-white ring-1 ring-emerald-500 dark:bg-slate-900' => $t->id === $turn->id])>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $t->created_at->format('j M, H:i') }} · {{ $t->branch ?? '-' }}</p>
                                <p class="text-slate-900 dark:text-slate-100 break-words"><span class="font-semibold">Visitor:</span> {{ $t->user_text }}</p>
                                <p class="text-slate-600 dark:text-slate-300 whitespace-pre-line break-words"><span class="font-semibold">Bot:</span> {{ $t->reply_text }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                {{ $filter === 'attention' ? 'Nothing needs attention. 🎉' : 'No messages yet.' }}
            </div>
        @endforelse
    </div>

    <div>{{ $turns->links() }}</div>
</div>
