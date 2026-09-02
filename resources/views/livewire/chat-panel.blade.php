<div class="h-[calc(100dvh-8rem)] lg:h-[75vh] min-h-[420px] lg:min-h-[500px] bg-white flex flex-col lg:flex-row text-slate-900 lg:rounded-2xl lg:border border-slate-200 lg:shadow-sm lg:overflow-hidden dark:bg-slate-900 dark:text-slate-100 dark:border-slate-800">
    <!-- Sidebar: Recipients / Search. Below `lg`, this is a whole screen (WhatsApp-style
         list), shown only while showList is true; at `lg`+ it's a fixed side column
         shown alongside the conversation regardless of showList. -->
    <div class="{{ $showList ? 'flex' : 'hidden' }} lg:flex w-full lg:w-80 shrink-0 bg-stone-50 lg:border-r border-slate-200 flex-col overflow-y-auto dark:bg-slate-950 dark:border-slate-800">
        <!-- Header -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Messages</h2>
        </div>

        <!-- Search / Tabs (Admin Only) -->
        @if(auth()->user()->isAdmin() || auth()->user()->isCaretaker())
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
                <!-- Tabs -->
                <div class="flex gap-2">
                    <button wire:click="switchTab('direct')"
                        class="flex-1 py-2 px-3 text-sm font-medium rounded-lg transition {{ $activeTab === 'direct' ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                        Direct
                    </button>
                    <button wire:click="switchTab('broadcast')"
                        class="flex-1 py-2 px-3 text-sm font-medium rounded-lg transition {{ $activeTab === 'broadcast' ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                        Broadcast
                    </button>
                </div>

                <!-- Search Inputs (Direct Tab Only) -->
                @if($activeTab === 'direct')
                    <div>
                        <input type="text" wire:model.live.debounce.300ms="searchHouse"
                            placeholder="Search by house..."
                            class="w-full px-3 py-2 border border-slate-300 bg-white text-slate-900 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <input type="text" wire:model.live.debounce.300ms="searchTenant"
                            placeholder="Search by name..."
                            class="w-full px-3 py-2 border border-slate-300 bg-white text-slate-900 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                @endif
            </div>
        @endif

        <!-- Recipients List -->
        @if($activeTab === 'direct')
            <div class="flex-1 overflow-y-auto bg-white dark:bg-slate-950">
                @forelse($filteredRecipients as $recipient)
                    <button wire:key="recipient-{{ $recipient->id }}" wire:click="selectRecipient({{ $recipient->id }})"
                        class="w-full text-left p-4 border-b border-slate-100 dark:border-slate-800 hover:bg-stone-50 dark:hover:bg-slate-900 transition {{ $recipientId === $recipient->id ? 'bg-emerald-50 dark:bg-emerald-500/10 border-l-4 border-l-emerald-600' : '' }}">
                        <div class="flex justify-between items-center">
                            <div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200 text-sm">{{ $recipient->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $recipient->email }}</div>
                            </div>
                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ optional($recipient->house)->house_name }}</div>
                        </div>
                    </button>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-slate-500 dark:text-slate-400 text-sm">No contacts found</p>
                    </div>
                @endforelse
            </div>
        @else
            <!-- Broadcast Info -->
            <div class="flex-1 flex items-center justify-center p-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-emerald-700 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.961 1.961 0 01-2.437-2.286L5.724 13H3a2 2 0 01-2-2V9a2 2 0 012-2h2.724l1.066-3.958a1.961 1.961 0 012.437-1.161zM15.172 3.172a4 4 0 018.364 1.636M15 12a4 4 0 018 0m4 0a8 8 0 11-16 0 8 8 0 0116 0z"></path>
                        </svg>
                    </div>
                    <p class="text-slate-800 dark:text-slate-200 font-semibold">Send to All Tenants</p>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ \App\Models\User::where('role', 'tenant')->count() }} tenants</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Chat Area. Below `lg` this is its own full screen, shown only once a
         conversation/composer is open (showList false); at `lg`+ it's always visible
         next to the sidebar. -->
    <div class="{{ $showList ? 'hidden' : 'flex' }} lg:flex flex-1 flex-col bg-white dark:bg-slate-900 min-w-0">
        <!-- Chat Header -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-950 flex items-center gap-3">
            <button wire:click="backToList" class="lg:hidden -ml-1 p-1 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 flex-shrink-0" aria-label="Back to messages">
                @svg('heroicon-o-chevron-left', 'w-5 h-5')
            </button>
            <div class="min-w-0 flex-1">
                @if($activeTab === 'direct')
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                        {{ $filteredRecipients->where('id', $recipientId)->first()?->name ?? 'Select a recipient' }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $filteredRecipients->where('id', $recipientId)->first()?->email ?? '' }}</p>
                @else
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Broadcast Message</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Send to all {{ \App\Models\User::where('role', 'tenant')->count() }} tenants</p>
                @endif
            </div>
        </div>

        @if($activeTab === 'direct')
            <!-- Messages Display -->
            <div wire:poll.3s class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-box">
                @forelse($this->messages as $msg)
                    @php $isMe = $msg->sender_id === auth()->id(); @endphp
                    <div wire:key="message-{{ $msg->id }}" class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xs px-4 py-2 rounded-2xl {{ $isMe ? 'bg-emerald-600 text-white' : 'bg-stone-100 text-slate-900 dark:bg-slate-800 dark:text-slate-100' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-xs {{ $isMe ? 'text-emerald-100' : 'text-slate-500 dark:text-slate-400' }}">{{ $msg->created_at->format('M d, H:i') }}</div>
                                <button wire:click.stop="replyToMessage({{ $msg->id }})" class="text-xs {{ $isMe ? 'text-emerald-100 hover:text-white' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }} hover:underline">Reply</button>
                            </div>

                            @if($msg->parent)
                                <div class="mt-2 px-3 py-2 rounded bg-black/5 dark:bg-white/10 text-sm">
                                    <div class="text-xs opacity-70">Replying to {{ $msg->parent->sender->name }}</div>
                                    <div class="mt-1 italic">{{ \Illuminate\Support\Str::limit($msg->parent->body, 100) }}</div>
                                </div>
                            @endif

                            <p class="text-sm break-words mt-2">{!! nl2br(e($msg->body)) !!}</p>
                            @if($msg->issue)
                                <div class="text-xs mt-1 font-semibold opacity-75">Issue #{{ $msg->issue->id }}</div>
                            @endif

                            @if($msg->attachment_type && $msg->attachment)
                                @php $att = $msg->attachment; @endphp
                                <div class="mt-2 rounded-lg border {{ $isMe ? 'border-white/30 bg-white/10' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900' }} px-3 py-2">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold {{ $isMe ? 'text-white' : 'text-slate-700 dark:text-slate-300' }}">
                                        @svg(match($msg->attachment_type) {
                                            'invoice' => 'heroicon-o-document-text',
                                            'bill' => 'heroicon-o-receipt-percent',
                                            'payment' => 'heroicon-o-banknotes',
                                            'notice' => 'heroicon-o-exclamation-triangle',
                                            default => 'heroicon-o-paper-clip',
                                        }, 'w-4 h-4 flex-shrink-0')
                                        <span>{{ $msg->attachment_type === 'notice' ? 'Notice to vacate' : ucfirst($msg->attachment_type) }}</span>
                                    </div>
                                    <div class="mt-1 text-sm {{ $isMe ? 'text-white' : 'text-slate-900 dark:text-slate-100' }}">
                                        @switch($msg->attachment_type)
                                            @case('invoice')
                                                {{ $att->invoice_number }} &middot; KES {{ number_format($att->amount) }}
                                                <div class="text-xs {{ $isMe ? 'text-emerald-100' : 'text-slate-500 dark:text-slate-400' }}">{{ ucfirst($att->status) }}</div>
                                                @break
                                            @case('bill')
                                                {{ \Carbon\Carbon::parse($att->bill_month)->format('M Y') }} &middot; KES {{ number_format($att->water + $att->electricity + $att->internet + $att->trash) }}
                                                @break
                                            @case('payment')
                                                KES {{ number_format($att->amount_paid) }} paid
                                                <div class="text-xs {{ $isMe ? 'text-emerald-100' : 'text-slate-500 dark:text-slate-400' }}">{{ $att->payment_date ? \Carbon\Carbon::parse($att->payment_date)->format('M j, Y') : '' }}</div>
                                                @break
                                            @case('notice')
                                                Vacate by {{ $att->vacate_date->format('M j, Y') }}
                                                <div class="text-xs {{ $isMe ? 'text-emerald-100' : 'text-slate-500 dark:text-slate-400' }}">{{ ucfirst($att->status) }}</div>
                                                @break
                                        @endswitch
                                        @if($att->relationLoaded('tenant') && $att->tenant)
                                            <div class="text-xs {{ $isMe ? 'text-emerald-100' : 'text-slate-400 dark:text-slate-500' }} mt-0.5">{{ $att->tenant->tenant_name }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex items-center justify-center h-full text-slate-400 dark:text-slate-500">
                        <p class="text-sm">No messages yet. Start the conversation!</p>
                    </div>
                @endforelse
            </div>

            <!-- Message Input Form -->
            <form wire:submit.prevent="sendMessage" class="p-3 border-t border-slate-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-950 space-y-2">
                @php $recipient = $filteredRecipients->where('id', $recipientId)->first(); @endphp
                @if(($this->messages->count() === 0 && (auth()->user()->isAdmin() || auth()->user()->isCaretaker())) || (auth()->user()->isTenant() && optional($recipient)->role === 'admin'))
                    <div class="grid grid-cols-2 gap-3">
                        @if((auth()->user()->isAdmin() || auth()->user()->isCaretaker()) && $this->messages->count() === 0)
                            <div>
                                <label class="block text-xs text-slate-600 dark:text-slate-400 font-semibold mb-1">House</label>
                                <select wire:model="houseId" class="w-full px-3 py-2 border border-slate-300 bg-white text-slate-900 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="">Select house...</option>
                                    @foreach($houses as $h)
                                        <option value="{{ $h->id }}">{{ $h->house_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div></div>
                        @endif

                        @if(auth()->user()->isTenant() && optional($recipient)->role === 'admin')
                            <div>
                                <label class="block text-xs text-slate-600 dark:text-slate-400 font-semibold mb-1">Issue</label>
                                <select wire:model="issueId" class="w-full px-3 py-2 border border-slate-300 bg-white text-slate-900 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="">Tag an issue...</option>
                                    @foreach($issues as $i)
                                        <option value="{{ $i->id }}">#{{ $i->id }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div></div>
                        @endif
                    </div>
                @endif

                @if($replyTo)
                    <div class="p-2 bg-stone-100 dark:bg-slate-800 rounded-lg text-sm text-slate-700 dark:text-slate-300 flex justify-between items-center">
                        <div>Replying to <span class="font-semibold">{{ optional(\App\Models\Message::find($replyTo))->sender->name ?? '...' }}</span></div>
                        <button type="button" wire:click="cancelReply" class="text-rose-600 dark:text-rose-400 text-sm font-medium">Cancel</button>
                    </div>
                @endif

                <!-- Attach panel: category picker, then a scoped/searchable list of records -->
                @if($showAttachPanel)
                    <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 space-y-2 max-h-56 overflow-y-auto">
                        @if(!$attachCategory)
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" wire:click="pickAttachCategory('invoice')" class="flex flex-col items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    @svg('heroicon-o-document-text', 'w-5 h-5 text-emerald-600 dark:text-emerald-400')
                                    Invoice
                                </button>
                                <button type="button" wire:click="pickAttachCategory('bill')" class="flex flex-col items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    @svg('heroicon-o-receipt-percent', 'w-5 h-5 text-emerald-600 dark:text-emerald-400')
                                    Bill
                                </button>
                                <button type="button" wire:click="pickAttachCategory('payment')" class="flex flex-col items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    @svg('heroicon-o-banknotes', 'w-5 h-5 text-emerald-600 dark:text-emerald-400')
                                    Payment
                                </button>
                                <button type="button" wire:click="pickAttachCategory('notice')" class="flex flex-col items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-emerald-600 dark:text-emerald-400')
                                    Notice
                                </button>
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <button type="button" wire:click="pickAttachCategory(null)" class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center gap-1">
                                    @svg('heroicon-o-chevron-left', 'w-3.5 h-3.5')
                                    Back
                                </button>
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 capitalize">{{ $attachCategory === 'notice' ? 'Notices to vacate' : $attachCategory . 's' }}</span>
                            </div>

                            @if($this->canBrowseAllAttachments())
                                <input type="text" wire:model.live.debounce.300ms="attachSearch" placeholder="Search by tenant or reference..."
                                    class="w-full px-3 py-1.5 border border-slate-300 bg-white text-slate-900 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @endif

                            <div class="space-y-1">
                                @forelse($this->attachOptions as $opt)
                                    <button type="button" wire:key="attach-opt-{{ $attachCategory }}-{{ $opt->id }}"
                                        wire:click="selectAttachment('{{ $attachCategory }}', {{ $opt->id }})"
                                        class="w-full text-left px-3 py-2 rounded-lg border border-slate-100 dark:border-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:border-emerald-200 dark:hover:border-emerald-500/30 transition">
                                        @switch($attachCategory)
                                            @case('invoice')
                                                <div class="text-xs font-medium text-slate-800 dark:text-slate-200">{{ $opt->invoice_number }} &middot; KES {{ number_format($opt->amount) }}</div>
                                                @break
                                            @case('bill')
                                                <div class="text-xs font-medium text-slate-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($opt->bill_month)->format('M Y') }} &middot; KES {{ number_format($opt->water + $opt->electricity + $opt->internet + $opt->trash) }}</div>
                                                @break
                                            @case('payment')
                                                <div class="text-xs font-medium text-slate-800 dark:text-slate-200">KES {{ number_format($opt->amount_paid) }}{{ $opt->payment_date ? ' · ' . \Carbon\Carbon::parse($opt->payment_date)->format('M j, Y') : '' }}</div>
                                                @break
                                            @case('notice')
                                                <div class="text-xs font-medium text-slate-800 dark:text-slate-200">Vacate {{ $opt->vacate_date->format('M j, Y') }}</div>
                                                @break
                                        @endswitch
                                        @if($opt->relationLoaded('tenant') && $opt->tenant)
                                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $opt->tenant->tenant_name }}</div>
                                        @endif
                                    </button>
                                @empty
                                    <p class="text-xs text-slate-400 dark:text-slate-500 text-center py-3">Nothing found.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                @endif

                @if($this->selectedAttachmentLabel)
                    <div class="flex items-center justify-between gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                        <span class="font-medium truncate">{{ $this->selectedAttachmentLabel }}</span>
                        <button type="button" wire:click="clearAttachment" class="text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 flex-shrink-0" aria-label="Remove attachment">
                            @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                        </button>
                    </div>
                @endif

                <div class="flex gap-2 items-end">
                    <button type="button" wire:click="toggleAttachPanel"
                        class="p-2.5 rounded-lg border transition flex-shrink-0 {{ $showAttachPanel || $this->selectedAttachmentLabel ? 'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-400' : 'border-slate-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-emerald-600 dark:hover:text-emerald-400' }}"
                        aria-label="Attach a document">
                        @svg('heroicon-o-paper-clip', 'w-5 h-5')
                    </button>
                    <textarea id="message-input" wire:model.defer="body" placeholder="Type your message..."
                        class="flex-1 px-4 py-2.5 border border-slate-300 bg-white text-slate-900 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        rows="2"></textarea>
                    <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition flex items-center gap-2 flex-shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="sendMessage">
                            <path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.837654326,3.0486314 1.15159189,3.97788954 L3.03521743,10.4188826 C3.03521743,10.5759799 3.34915502,10.7330773 3.50612381,10.7330773 L16.6915026,11.5185642 C16.6915026,11.5185642 17.1624089,11.5185642 17.1624089,12.0374526 C17.1624089,12.5563409 16.6915026,12.4744748 16.6915026,12.4744748 Z"></path>
                        </svg>
                        <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" wire:loading wire:target="sendMessage">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75"></path>
                        </svg>
                        <span>Send</span>
                    </button>
                </div>
            </form>
        @else
            <!-- Broadcast Form -->
            <form wire:submit.prevent="sendBroadcast" class="flex-1 flex flex-col p-8 space-y-4">
                @if(session('success'))
                    <div class="p-4 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-sm font-semibold dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                         {{ session('success') }}
                    </div>
                @endif

                <textarea wire:model.defer="broadcastMsg"
                    placeholder="Write your broadcast message here..."
                    class="flex-1 px-4 py-3 border-2 border-slate-300 bg-white text-slate-900 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </textarea>

                <div class="flex justify-end">
                    <button type="submit" wire:loading.attr="disabled" wire:target="sendBroadcast" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="sendBroadcast">
                            <path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.837654326,3.0486314 1.15159189,3.97788954 L3.03521743,10.4188826 C3.03521743,10.5759799 3.34915502,10.7330773 3.50612381,10.7330773 L16.6915026,11.5185642 C16.6915026,11.5185642 17.1624089,11.5185642 17.1624089,12.0374526 C17.1624089,12.5563409 16.6915026,12.4744748 16.6915026,12.4744748 Z"></path>
                        </svg>
                        <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" wire:loading wire:target="sendBroadcast">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75"></path>
                        </svg>
                        <span>Send Broadcast</span>
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
    // Livewire v3 no longer dispatches a "livewire:update" DOM event (that was v2) -
    // use the morph.updated component hook instead so scroll/focus actually run.
    document.addEventListener('livewire:init', () => {
        Livewire.hook('morph.updated', () => {
            const el = document.getElementById('messages-box');
            if (el) setTimeout(() => el.scrollTop = el.scrollHeight, 100);

            const inp = document.getElementById('message-input');
            if (inp && document.activeElement !== inp) setTimeout(() => inp.focus(), 50);
        });
    });
</script>
