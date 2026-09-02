<?php

namespace App\Livewire;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Small unread-count pill dropped next to any "Chat" nav item - see
 *  ChatPanel::getMessagesProperty(), which marks messages read once their
 *  conversation is actually opened. */
class ChatUnreadBadge extends Component
{
    public function render()
    {
        return view('livewire.chat-unread-badge', [
            'count' => Message::where('receiver_id', Auth::id())->whereNull('read_at')->count(),
        ]);
    }
}
