<?php

namespace App\Livewire\SuperadminApp;

use App\Models\ChatAlias;
use App\Models\ChatTurn;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * App-shell counterpart to Filament's "Chat failure inbox": the visitor
 * messages the assistant couldn't answer, each one teachable in a tap.
 */
class ChatInbox extends Component
{
    use WithPagination;

    public string $filter = 'attention';

    public string $search = '';

    public ?int $threadFor = null;

    public ?int $teachingId = null;

    public string $teachType = 'area';

    public string $teachPhrase = '';

    public string $teachCanonical = '';

    public string $teachLanguage = 'sw';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function toggleThread(int $turnId): void
    {
        $this->threadFor = $this->threadFor === $turnId ? null : $turnId;
    }

    public function startTeaching(int $turnId): void
    {
        $turn = ChatTurn::findOrFail($turnId);

        $this->teachingId = $turnId;
        $this->teachType = 'area';
        $this->teachPhrase = mb_strtolower($turn->user_text);
        $this->teachCanonical = '';
        $this->teachLanguage = $turn->language === 'en' ? 'en' : 'sw';
        $this->resetValidation();
    }

    public function cancelTeaching(): void
    {
        $this->teachingId = null;
    }

    public function saveTeaching(): void
    {
        $needsMeaning = in_array($this->teachType, ['area', 'house_type'], true);

        $this->validate([
            'teachType' => 'required|in:'.implode(',', array_keys(ChatAlias::TYPES)),
            'teachPhrase' => 'required|string|max:255',
            'teachCanonical' => [$needsMeaning ? 'required' : 'nullable', 'string', 'max:255', function ($attribute, $value, $fail) use ($needsMeaning) {
                if ($needsMeaning && ! array_key_exists($value, ChatAlias::canonicalOptions($this->teachType))) {
                    $fail('Pick one of the listed options.');
                }
            }],
            'teachLanguage' => 'nullable|in:sw,en',
        ]);

        ChatAlias::updateOrCreate(
            ['type' => $this->teachType, 'phrase' => mb_strtolower(trim($this->teachPhrase))],
            ['canonical' => $needsMeaning ? $this->teachCanonical : null, 'language' => $this->teachLanguage ?: null, 'is_active' => true],
        );

        ChatTurn::whereKey($this->teachingId)->update(['status' => 'taught']);

        $this->teachingId = null;
        session()->flash('chat-inbox-saved', 'Phrase learned - the assistant will understand it within a few minutes.');
    }

    public function ignore(int $turnId): void
    {
        ChatTurn::whereKey($turnId)->whereNull('status')->update(['status' => 'ignored']);
    }

    public function render()
    {
        $turns = ChatTurn::query()
            ->when($this->filter === 'attention', fn ($q) => $q->needsAttention())
            ->when($this->search !== '', fn ($q) => $q->where('user_text', 'like', '%'.$this->search.'%'))
            ->latest('id')
            ->paginate(15);

        $thread = $this->threadFor
            ? ChatTurn::where('chat_id', ChatTurn::whereKey($this->threadFor)->value('chat_id'))->orderBy('id')->get()
            : collect();

        return view('livewire.superadmin-app.chat-inbox', [
            'turns' => $turns,
            'thread' => $thread,
            'attentionCount' => ChatTurn::needsAttention()->count(),
            'types' => ChatAlias::TYPES,
            'canonicalOptions' => ChatAlias::canonicalOptions($this->teachType),
        ])->layout('components.layouts.app', ['title' => 'Chat failure inbox']);
    }
}
