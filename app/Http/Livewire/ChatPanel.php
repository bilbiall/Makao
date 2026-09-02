<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\User;
use App\Models\House;
use App\Models\Issue;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\NoticeToVacate;
use App\Models\Payment;
use App\Models\Tenant;
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

    // On mobile the list and the open conversation are two screens, not two panes
    // (WhatsApp-style) - true shows the recipient list, false shows the open
    // conversation/broadcast composer. Ignored above the `lg` breakpoint, where the
    // blade view forces both panes visible regardless of this flag.
    public bool $showList = true;

    // Attaching an invoice/payment/notice-to-vacate to the message being composed -
    // available to every role, scoped to whichever tenant is on the other end of the
    // open conversation (see relevantTenant()).
    public bool $showAttachPanel = false;
    public ?string $attachCategory = null; // 'invoice' | 'bill' | 'payment' | 'notice', while picking
    public ?string $attachType = null; // same four values, once picked
    public ?int $attachId = null;
    // Only used while browsing attachments with no tenant tied to the open
    // conversation (see canBrowseAllAttachments()) - lets admin/caretaker/manager/
    // landlord staff messaging each other search across every tenant's records.
    public string $attachSearch = '';

    protected $rules = [
        'recipientId' => 'required|integer',
        'body' => 'required|string|max:2000',
    ];

    public function mount()
    {
        $user = Auth::user();

        if ($user->isAdmin() || $user->isManager() || $user->isCaretaker() || $user->isLandlord()) {
            // Admin/Caretaker/Landlord view: load all tenants
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
        // Load all of this landlord's tenant users for admin/caretaker/landlord to message.
        // User carries no automatic landlord scope, so this must be filtered explicitly.
        $landlordId = Auth::user()->landlord_id;
        $this->caretakers = User::where('role', 'tenant')->where('landlord_id', $landlordId)->orderBy('name')->get();
        $this->filteredRecipients = $this->caretakers;
        if ($this->caretakers->count() > 0 && !$this->recipientId) {
            $this->recipientId = $this->caretakers->first()->id;
        }
    }

    public function loadTenantRecipients()
    {
        // Load this tenant's own landlord's admin/caretaker/landlord users to message.
        $landlordId = Auth::user()->landlord_id;
        $this->caretakers = User::whereIn('role', ['admin', 'manager', 'caretaker', 'landlord'])->where('landlord_id', $landlordId)->orderBy('name')->get();
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

        if ($user->isAdmin() || $user->isManager() || $user->isCaretaker() || $user->isLandlord()) {
            // Admin/landlord: search this landlord's own tenants by house name or tenant name
            $query = User::where('role', 'tenant')->where('landlord_id', $user->landlord_id);

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
            // Tenant: no special filtering, just show this landlord's admins/caretakers
            $query = User::whereIn('role', ['admin', 'manager', 'caretaker', 'landlord'])->where('landlord_id', $user->landlord_id);
        }
        
        $this->filteredRecipients = $query->orderBy('name')->get();

        // If the currently selected recipient is no longer in the filtered set,
        // fall back to the first visible match instead of leaving a stale/hidden selection.
        if ($this->recipientId && !$this->filteredRecipients->contains('id', $this->recipientId)) {
            $this->recipientId = null;
        }

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
        $this->showList = false;
        // Attachable records are scoped to whichever tenant is on the other end
        // of the conversation, so a selection made for the previous recipient
        // can't carry over.
        $this->clearAttachment();
        $this->showAttachPanel = false;
        $this->attachCategory = null;
        $this->attachSearch = '';
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        // "Direct" goes back to the recipient list; "Broadcast" opens its composer
        // directly - matches selectRecipient()'s own showList handling below.
        $this->showList = $tab === 'direct';
    }

    public function backToList()
    {
        $this->showList = true;
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

        // recipientId is a public Livewire property and could be tampered with in a crafted
        // request, so re-verify the receiver belongs to the same landlord as the sender
        // before creating anything - the filteredRecipients list alone is not enforcement.
        $receiver = User::find($this->recipientId);
        if (!$receiver || $receiver->landlord_id !== Auth::user()->landlord_id) {
            session()->flash('error', 'Invalid recipient.');
            return;
        }

        // Ensure issue is only attached when messaging an admin account
        $issueId = null;
        if ($receiver->role === 'admin') {
            $issueId = $this->issueId;
        }

        // attachType/attachId are public Livewire properties too - re-verify the
        // referenced record actually belongs to this conversation's tenant (or, for
        // a conversation with no tenant on either end, to this landlord) before
        // saving - the same defense-in-depth as the recipientId check above.
        $attachmentType = null;
        $attachmentId = null;
        if ($this->attachType && $this->attachId) {
            $tenant = $this->relevantTenant();
            $isValid = false;

            if ($tenant) {
                $isValid = match ($this->attachType) {
                    'invoice' => Invoice::where('tenant_id', $tenant->id)->whereKey($this->attachId)->exists(),
                    'bill' => Bill::where('tenant_id', $tenant->id)->whereKey($this->attachId)->exists(),
                    'payment' => Payment::where('tenant_id', $tenant->id)->whereKey($this->attachId)->exists(),
                    'notice' => NoticeToVacate::where('tenant_id', $tenant->id)->whereKey($this->attachId)->exists(),
                    default => false,
                };
            } elseif ($this->canBrowseAllAttachments()) {
                $landlordId = Auth::user()->landlord_id;
                $isValid = match ($this->attachType) {
                    'invoice' => Invoice::where('landlord_id', $landlordId)->whereKey($this->attachId)->exists(),
                    'bill' => Bill::where('landlord_id', $landlordId)->whereKey($this->attachId)->exists(),
                    'payment' => Payment::where('landlord_id', $landlordId)->whereKey($this->attachId)->exists(),
                    'notice' => NoticeToVacate::where('landlord_id', $landlordId)->whereKey($this->attachId)->exists(),
                    default => false,
                };
            }

            if ($isValid) {
                $attachmentType = $this->attachType;
                $attachmentId = $this->attachId;
            }
        }

        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->recipientId,
            'house_id' => $this->houseId,
            'issue_id' => $issueId,
            'parent_id' => $this->replyTo,
            'body' => $this->body,
            'attachment_type' => $attachmentType,
            'attachment_id' => $attachmentId,
        ]);

        // Send email notification to tenant recipients
        if ($receiver && $receiver->role === 'tenant' && $receiver->email) {
            $sender = Auth::user();
            $body = EmailTemplateHelper::render('message', [
                'tenant_name' => $receiver->name ?? $receiver->email,
                'sender_name' => $sender?->name ?? 'System',
                'message_body' => $this->body,
            ], $receiver->landlord_id);

            try {
                EmailHelper::send(
                    $receiver->email,
                    'New message from ' . ($sender?->name ?? \App\Helpers\AppHelper::getAppName($receiver->landlord_id)),
                    $body,
                    $receiver->landlord_id
                );
            } catch (\Throwable $e) {
                // ignore email failures (e.g. SMTP not configured) - the Message row is already saved
            }
        }

        $this->body = '';
        $this->issueId = null;
        $this->replyTo = null;
        $this->clearAttachment();
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

    /**
     * The tenant these attachable records (invoices/payments/notices) should come
     * from - my own tenancy if I'm a tenant, otherwise the recipient's, if they're
     * a tenant. Null when neither side of the conversation is a tenant (e.g. one
     * admin messaging another, or the broadcast composer).
     */
    protected function relevantTenant(): ?Tenant
    {
        $user = Auth::user();
        if ($user->isTenant()) {
            return $user->tenant;
        }

        $recipient = $this->recipientId ? User::find($this->recipientId) : null;
        return $recipient?->role === 'tenant' ? $recipient->tenant : null;
    }

    /**
     * Whether the current user may browse attachable records across every
     * tenant rather than just one - true only for admin/manager/caretaker/
     * landlord staff messaging each other, where relevantTenant() has nobody
     * to scope to.
     */
    public function canBrowseAllAttachments(): bool
    {
        $user = Auth::user();
        $isStaff = $user->isAdmin() || $user->isManager() || $user->isCaretaker() || $user->isLandlord();

        return $isStaff && !$this->relevantTenant();
    }

    public function toggleAttachPanel()
    {
        $this->showAttachPanel = !$this->showAttachPanel;
        $this->attachCategory = null;
        $this->attachSearch = '';
    }

    public function pickAttachCategory(?string $category)
    {
        $this->attachCategory = $category;
        $this->attachSearch = '';
    }

    public function updatedAttachSearch()
    {
        // no-op: getAttachOptionsProperty() re-runs on every render, this just
        // exists so wire:model.live has something to hang the request on.
    }

    public function selectAttachment(string $type, int $id)
    {
        $this->attachType = $type;
        $this->attachId = $id;
        $this->showAttachPanel = false;
        $this->attachCategory = null;
        $this->attachSearch = '';
    }

    public function clearAttachment()
    {
        $this->attachType = null;
        $this->attachId = null;
    }

    public function getAttachOptionsProperty()
    {
        if (!$this->attachCategory) {
            return collect();
        }

        $tenant = $this->relevantTenant();

        if ($tenant) {
            return match ($this->attachCategory) {
                'invoice' => Invoice::where('tenant_id', $tenant->id)->latest('invoice_date')->limit(10)->get(),
                'bill' => Bill::where('tenant_id', $tenant->id)->latest('bill_month')->limit(10)->get(),
                'payment' => Payment::where('tenant_id', $tenant->id)->latest('payment_date')->limit(10)->get(),
                'notice' => NoticeToVacate::where('tenant_id', $tenant->id)->latest('vacate_date')->limit(10)->get(),
                default => collect(),
            };
        }

        if (!$this->canBrowseAllAttachments()) {
            return collect();
        }

        // No tenant tied to this conversation (staff messaging each other) -
        // browse every one of this landlord's records instead, searchable by
        // tenant name or reference number.
        $landlordId = Auth::user()->landlord_id;
        $search = trim($this->attachSearch);

        return match ($this->attachCategory) {
            'invoice' => Invoice::where('landlord_id', $landlordId)
                ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', "%{$search}%"));
                }))
                ->with('tenant')->latest('invoice_date')->limit(15)->get(),
            'bill' => Bill::where('landlord_id', $landlordId)
                ->when($search, fn ($q) => $q->whereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', "%{$search}%")))
                ->with('tenant')->latest('bill_month')->limit(15)->get(),
            'payment' => Payment::where('landlord_id', $landlordId)
                ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                    $q->where('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', "%{$search}%"));
                }))
                ->with('tenant')->latest('payment_date')->limit(15)->get(),
            'notice' => NoticeToVacate::where('landlord_id', $landlordId)
                ->when($search, fn ($q) => $q->whereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', "%{$search}%")))
                ->with('tenant')->latest('vacate_date')->limit(15)->get(),
            default => collect(),
        };
    }

    public function getSelectedAttachmentLabelProperty(): ?string
    {
        if (!$this->attachType || !$this->attachId) {
            return null;
        }

        return match ($this->attachType) {
            'invoice' => ($m = Invoice::with('tenant')->find($this->attachId))
                ? "Invoice {$m->invoice_number} \u{b7} KES " . number_format($m->amount) . $this->attachmentTenantSuffix($m->tenant)
                : null,
            'bill' => ($m = Bill::with('tenant')->find($this->attachId))
                ? "Bill \u{b7} " . \Carbon\Carbon::parse($m->bill_month)->format('M Y') . $this->attachmentTenantSuffix($m->tenant)
                : null,
            'payment' => ($m = Payment::with('tenant')->find($this->attachId))
                ? 'Payment KES ' . number_format($m->amount_paid) . ($m->payment_date ? " \u{b7} " . \Carbon\Carbon::parse($m->payment_date)->format('M j, Y') : '') . $this->attachmentTenantSuffix($m->tenant)
                : null,
            'notice' => ($m = NoticeToVacate::with('tenant')->find($this->attachId))
                ? "Notice to vacate \u{b7} " . $m->vacate_date->format('M j, Y') . $this->attachmentTenantSuffix($m->tenant)
                : null,
            default => null,
        };
    }

    /** Appended to the selected-attachment label only when browsing across
     *  every tenant, so staff can tell whose record they just picked. */
    protected function attachmentTenantSuffix(?Tenant $tenant): string
    {
        return ($tenant && $this->canBrowseAllAttachments()) ? " \u{b7} {$tenant->tenant_name}" : '';
    }

    public function sendBroadcast()
    {
        $this->validate(['broadcastMsg' => 'required|string|max:2000']);
        
        $user = Auth::user();

        // Only admins/caretakers/landlords can broadcast
        if (!($user->isAdmin() || $user->isManager() || $user->isCaretaker() || $user->isLandlord())) {
            session()->flash('error', 'Only admins can broadcast messages.');
            return;
        }

        // Send to this landlord's own tenant users only
        $tenants = User::where('role', 'tenant')->where('landlord_id', $user->landlord_id)->get();
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
                ], $tenant->landlord_id);

                try {
                    EmailHelper::send(
                        $tenant->email,
                        'New broadcast message from ' . ($sender?->name ?? \App\Helpers\AppHelper::getAppName($tenant->landlord_id)),
                        $body,
                        $tenant->landlord_id
                    );
                } catch (\Throwable $e) {
                    // ignore email failures for this recipient and keep broadcasting to the rest
                }
            }
        }
        
        $this->broadcastMsg = '';
        session()->flash('success', 'Broadcast sent to ' . $tenants->count() . ' tenants.');
    }

    public function getMessagesProperty()
    {
        if (!$this->recipientId) return collect();
        $me = Auth::id();

        // Viewing this conversation is what "reads" it - clears the unread badge
        // (see ChatUnreadBadge) for messages this recipient sent me.
        Message::where('receiver_id', $me)
            ->where('sender_id', $this->recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Message::with(['sender', 'attachment' => function ($morphTo) {
                $morphTo->morphWith([
                    Invoice::class => ['tenant'],
                    Bill::class => ['tenant'],
                    Payment::class => ['tenant'],
                    NoticeToVacate::class => ['tenant'],
                ]);
            }])
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
