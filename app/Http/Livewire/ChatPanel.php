<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\User;
use App\Models\House;
use App\Models\Issue;
use App\Helpers\EmailHelper;
use App\Helpers\EmailTemplateHelper;

class ChatPanel extends Component
{
    public $recipientId;
    public $body = '';
    public $houseId = null;
    public $issueId = null;
    public $caretakers;
    public $houses;
    public $issues;
    public $searchHouse = '';
    public $searchTenant = '';
    public $broadcastMsg = '';
    public $showBroadcast = false;
    public $activeTab = 'direct';
    public $filteredRecipients = [];
    public $replyTo = null;

    protected $rules = [
        'recipientId' => 'required|integer',
        'body' => 'required|string|max:2000',
    ];

    public function mount()
    {
        $user = Auth::user();
        
        if ($user->isAdmin() || $user->isCaretaker()) {
            // Admin/Caretaker view: load all tenants
            $this->loadAdminRecipients();
        } else {
            // Tenant view: load admins/caretakers
            $this->loadTenantRecipients();
        }
        
        $this->houses = House::orderBy('house_name')->get();
        
        // Load issues for the current tenant
        if ($user->isTenant() && $user->tenant) {
            $this->issues = $user->tenant->issues;
        } else {
            $this->issues = collect();
        }
    }

    public function loadAdminRecipients()
    {
        // Load all tenant users for admin/caretaker to message
        $this->caretakers = User::where('role', 'tenant')->orderBy('name')->get();
        $this->filteredRecipients = $this->caretakers;
        if ($this->caretakers->count() > 0 && !$this->recipientId) {
            $this->recipientId = $this->caretakers->first()->id;
        }
    }

    public function loadTenantRecipients()
    {
        // Load admin/caretaker users for tenant to message
        $this->caretakers = User::whereIn('role', ['admin', 'caretaker'])->orderBy('name')->get();
        $this->filteredRecipients = $this->caretakers;
        if ($this->caretakers->count() > 0 && !$this->recipientId) {
            $this->recipientId = $this->caretakers->first()->id;
        }
    }

    public function updatedSearchHouse()
    {
        $this->filterRecipients();
    }

    public function updatedSearchTenant()
    {
        $this->filterRecipients();
    }

    public function filterRecipients()
    {
        $user = Auth::user();
        $query = null;

        if ($user->isAdmin() || $user->isCaretaker()) {
            // Admin: search tenants by house name or tenant name
            $query = User::where('role', 'tenant');
            
            if ($this->searchHouse) {
                // Find house by name, then get tenant in that house
                $houseIds = House::where('house_name', 'like', '%' . $this->searchHouse . '%')
                    ->pluck('id');
                $query->whereHas('tenant', function($q) use ($houseIds) {
                    $q->whereIn('house_id', $houseIds);
                });
            }
            
            if ($this->searchTenant) {
                $query->where('name', 'like', '%' . $this->searchTenant . '%');
            }
        } else {
            // Tenant: no special filtering, just show admins/caretakers
            $query = User::whereIn('role', ['admin', 'caretaker']);
        }
        
        $this->filteredRecipients = $query->orderBy('name')->get();
        
        // Auto-select first if available
        if ($this->filteredRecipients->count() > 0 && !$this->recipientId) {
            $this->recipientId = $this->filteredRecipients->first()->id;
        }
    }

    public function selectRecipient($userId)
    {
        $this->recipientId = $userId;
        $this->searchHouse = '';
        $this->searchTenant = '';
        $this->activeTab = 'direct';
        $this->replyTo = null;
    }

    public function updatedHouseId()
    {
        // Filter issues by house when house changes
        $user = Auth::user();
        if ($user->isTenant() && $user->tenant) {
            $this->issues = $user->tenant->issues()
                ->when($this->houseId, function($q) {
                    $q->whereHas('tenant', function($sq) {
                        $sq->where('house_id', $this->houseId);
                    });
                })
                ->get();
        }
    }

    public function sendMessage()
    {
        // If a house is selected, map it to the tenant's user and set recipient
        if ($this->houseId) {
            $house = House::find($this->houseId);
            if ($house && $house->tenant && $house->tenant->user) {
                $this->recipientId = $house->tenant->user->id;
            }
        }

        $this->validate();

        // Ensure issue is only attached when messaging an admin account
        $receiver = User::find($this->recipientId);
        $issueId = null;
        if ($receiver && $receiver->role === 'admin') {
            $issueId = $this->issueId;
        }

        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->recipientId,
            'house_id' => $this->houseId,
            'issue_id' => $issueId,
            'parent_id' => $this->replyTo,
            'body' => $this->body,
        ]);

        // Send email notification to tenant recipients
        if ($receiver && $receiver->role === 'tenant' && $receiver->email) {
            $sender = Auth::user();
            $body = EmailTemplateHelper::render('message', [
                'tenant_name' => $receiver->name ?? $receiver->email,
                'sender_name' => $sender?->name ?? 'System',
                'message_body' => $this->body,
            ]);

            EmailHelper::send(
                $receiver->email,
                'New message from ' . ($sender?->name ?? config('app.name')),
                $body
            );
        }

        $this->body = '';
        $this->issueId = null;
        $this->replyTo = null;
    }

    public function replyToMessage($messageId)
    {
        $message = Message::find($messageId);
        if (!$message) return;
        $this->replyTo = $message->id;
        // Optionally pre-fill body with quoted text or focus
        // Focusing will be handled by the frontend via the livewire:update hook
    }

    public function cancelReply()
    {
        $this->replyTo = null;
    }

    public function sendBroadcast()
    {
        $this->validate(['broadcastMsg' => 'required|string|max:2000']);
        
        $user = Auth::user();
        
        // Only admins/caretakers can broadcast
        if (!($user->isAdmin() || $user->isCaretaker())) {
            session()->flash('error', 'Only admins can broadcast messages.');
            return;
        }
        
        // Send to all tenant users
        $tenants = User::where('role', 'tenant')->get();
        foreach ($tenants as $tenant) {
            Message::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $tenant->id,
                'body' => '[BROADCAST] ' . $this->broadcastMsg,
            ]);

            if ($tenant->email) {
                $sender = Auth::user();
                $body = EmailTemplateHelper::render('message', [
                    'tenant_name' => $tenant->name ?? $tenant->email,
                    'sender_name' => $sender?->name ?? 'System',
                    'message_body' => $this->broadcastMsg,
                ]);

                EmailHelper::send(
                    $tenant->email,
                    'New broadcast message from ' . ($sender?->name ?? config('app.name')),
                    $body
                );
            }
        }
        
        $this->broadcastMsg = '';
        session()->flash('success', 'Broadcast sent to ' . $tenants->count() . ' tenants.');
    }

    public function getMessagesProperty()
    {
        if (!$this->recipientId) return collect();
        $me = Auth::id();
        return Message::with('sender')
            ->where(function($q) use ($me){
                $q->where('sender_id', $me)->orWhere('receiver_id', $me);
            })
            ->where(function($q){
                $q->where('sender_id', $this->recipientId)->orWhere('receiver_id', $this->recipientId);
            })
            ->orderBy('created_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.chat-panel');
    }
}
