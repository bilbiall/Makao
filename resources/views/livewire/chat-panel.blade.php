<div class="h-full bg-white dark:bg-gray-900 flex text-gray-900 dark:text-gray-100">
    <!-- Sidebar: Recipients / Search -->
    <div class="w-80 bg-gray-100 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Messages</h2>
        </div>

        <!-- Search / Tabs (Admin Only) -->
        @if(auth()->user()->isAdmin() || auth()->user()->isCaretaker())
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                <!-- Tabs -->
                <div class="flex gap-2">
                    <button wire:click="$set('activeTab', 'direct')" 
                        class="flex-1 py-2 px-3 text-sm rounded-lg transition {{ $activeTab === 'direct' ? 'bg-sky-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                        Direct
                    </button>
                    <button wire:click="$set('activeTab', 'broadcast')" 
                        class="flex-1 py-2 px-3 text-sm rounded-lg transition {{ $activeTab === 'broadcast' ? 'bg-emerald-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                        Broadcast
                    </button>
                </div>

                <!-- Search Inputs (Direct Tab Only) -->
                @if($activeTab === 'direct')
                    <div>
                        <input type="text" wire:model.debounce-300ms="searchHouse" 
                            placeholder="Search by house..." 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-500 dark:placeholder-gray-400">
                    </div>
                    <div>
                        <input type="text" wire:model.debounce-300ms="searchTenant" 
                            placeholder="Search by name..." 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-500 dark:placeholder-gray-400">
                    </div>
                @endif
            </div>
        @endif

        <!-- Recipients List -->
        @if($activeTab === 'direct')
            <div class="flex-1 overflow-y-auto bg-white dark:bg-gray-800">
                @forelse($filteredRecipients as $recipient)
                    <button wire:click="selectRecipient({{ $recipient->id }})"
                        class="w-full text-left p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ $recipientId === $recipient->id ? 'bg-sky-50 dark:bg-sky-900 border-l-4 border-l-sky-600' : '' }}">
                        <div class="flex justify-between items-center">
                            <div>
                                    <div class="font-medium text-gray-800 dark:text-gray-100 text-sm">{{ $recipient->name }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $recipient->email }}</div>
                            </div>
                            <div class="text-xs text-gray-400">{{ optional($recipient->house)->house_name }}</div>
                        </div>
                    </button>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No contacts found</p>
                    </div>
                @endforelse
            </div>
        @else
            <!-- Broadcast Info -->
            <div class="flex-1 flex items-center justify-center p-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.961 1.961 0 01-2.437-2.286L5.724 13H3a2 2 0 01-2-2V9a2 2 0 012-2h2.724l1.066-3.958a1.961 1.961 0 012.437-1.161zM15.172 3.172a4 4 0 018.364 1.636M15 12a4 4 0 018 0m4 0a8 8 0 11-16 0 8 8 0 0116 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 dark:text-white font-semibold">Send to All Tenants</p>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ \App\Models\User::where('role', 'tenant')->count() }} tenants</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col bg-white dark:bg-gray-900">
        <!-- Chat Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            @if($activeTab === 'direct')
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        {{ $filteredRecipients->where('id', $recipientId)->first()?->name ?? 'Select a recipient' }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $filteredRecipients->where('id', $recipientId)->first()?->email ?? '' }}</p>
                </div>
            @else
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Broadcast Message</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Send to all {{ \App\Models\User::where('role', 'tenant')->count() }} tenants</p>
            @endif
        </div>

        @if($activeTab === 'direct')
            <!-- Messages Display -->
            <div wire:poll.3s class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-box">
                @forelse($this->messages as $msg)
                    @php $isMe = $msg->sender_id === auth()->id(); @endphp
                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xs px-4 py-2 rounded-lg {{ $isMe ? 'bg-sky-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100' }}">
                            <div class="flex items-center justify-between">
                                <div class="text-xs {{ $isMe ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400' }}">{{ $msg->created_at->format('M d, H:i') }}</div>
                                <button wire:click.stop="replyToMessage({{ $msg->id }})" class="text-xs text-gray-500 hover:underline">Reply</button>
                            </div>

                            @if($msg->parent)
                                <div class="mt-2 px-3 py-2 rounded bg-gray-100 dark:bg-gray-900 text-sm text-gray-600">
                                    <div class="text-xs text-gray-500">Replying to {{ $msg->parent->sender->name }}</div>
                                    <div class="mt-1 italic">{{ \Illuminate\Support\Str::limit($msg->parent->body, 100) }}</div>
                                </div>
                            @endif

                            <p class="text-sm break-words mt-2">{!! nl2br(e($msg->body)) !!}</p>
                            @if($msg->issue)
                                <div class="text-xs mt-1 font-semibold opacity-75">Issue #{{ $msg->issue->id }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex items-center justify-center h-full text-gray-400 dark:text-gray-500">
                        <p class="text-sm">No messages yet. Start the conversation!</p>
                    </div>
                @endforelse
            </div>

            <!-- Message Input Form -->
            <form wire:submit.prevent="sendMessage" class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 space-y-3">
                @php $recipient = $filteredRecipients->where('id', $recipientId)->first(); @endphp
                <div class="grid grid-cols-2 gap-3">
                    @if((auth()->user()->isAdmin() || auth()->user()->isCaretaker()) && $this->messages->count() === 0)
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">House</label>
                            <select wire:model="houseId" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                            <label class="block text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Issue</label>
                            <select wire:model="issueId" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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

                @if($replyTo)
                    <div class="p-2 bg-gray-100 dark:bg-gray-900 rounded text-sm text-gray-700 dark:text-gray-300 flex justify-between items-center">
                        <div>Replying to <span class="font-semibold">{{ optional(\App\Models\Message::find($replyTo))->sender->name ?? '...' }}</span></div>
                        <button type="button" wire:click="cancelReply" class="text-red-500 text-sm">Cancel</button>
                    </div>
                @endif

                <div class="flex gap-2 items-end">
                    <textarea id="message-input" wire:model.defer="body" placeholder="Type your message..." 
                        class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-500 dark:placeholder-gray-400" 
                        rows="3"></textarea>
                    <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center gap-2 flex-shrink-0">
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
                    <div class="p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg text-sm font-semibold">
                         {{ session('success') }}
                    </div>
                @endif

                <textarea wire:model.defer="broadcastMsg" 
                    placeholder="Write your broadcast message here..." 
                    class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500 dark:placeholder-gray-400">
                </textarea>

                <div class="flex justify-end">
                    <button type="submit" wire:loading.attr="disabled" wire:target="sendBroadcast" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition flex items-center gap-2">
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
    document.addEventListener('livewire:update', function() {
        const el = document.getElementById('messages-box');
        if (el) setTimeout(() => el.scrollTop = el.scrollHeight, 100);
    });

    // Fallback: use standard livewire:update to scroll/focus after DOM updates
    document.addEventListener('livewire:update', function(e) {
        // If message input was the target of a reply, focus it
        const inp = document.getElementById('message-input');
        if (inp) setTimeout(() => inp.focus(), 50);

        const el = document.getElementById('messages-box');
        if (el) setTimeout(() => el.scrollTop = el.scrollHeight, 100);
    });
</script>
