<?php

namespace App\Livewire\SuperadminApp;

use App\Models\ChatAlias;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * App-shell counterpart to Filament's "Taught phrases": the words the chat
 * assistant has been taught (area nicknames, Swahili yes/no, unit-type phrases,
 * ways of asking for a person).
 */
class ChatPhrases extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $type = 'area';

    public string $phrase = '';

    public string $canonical = '';

    public string $language = 'sw';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'phrase', 'canonical']);
        $this->type = 'area';
        $this->language = 'sw';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $alias = ChatAlias::findOrFail($id);

        $this->editingId = $alias->id;
        $this->type = $alias->type;
        $this->phrase = $alias->phrase;
        $this->canonical = (string) $alias->canonical;
        $this->language = (string) $alias->language;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $needsMeaning = in_array($this->type, ['area', 'house_type'], true);
        $phrase = mb_strtolower(trim($this->phrase));

        $this->validate([
            'type' => 'required|in:'.implode(',', array_keys(ChatAlias::TYPES)),
            'phrase' => 'required|string|max:255',
            'canonical' => [$needsMeaning ? 'required' : 'nullable', 'string', 'max:255', function ($attribute, $value, $fail) use ($needsMeaning) {
                if ($needsMeaning && ! array_key_exists($value, ChatAlias::canonicalOptions($this->type))) {
                    $fail('Pick one of the listed options.');
                }
            }],
            'language' => 'nullable|in:sw,en',
        ]);

        $duplicate = ChatAlias::where('type', $this->type)->where('phrase', $phrase)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($duplicate) {
            $this->addError('phrase', 'The assistant already knows this phrase.');

            return;
        }

        $attributes = [
            'type' => $this->type,
            'phrase' => $phrase,
            'canonical' => $needsMeaning ? $this->canonical : null,
            'language' => $this->language ?: null,
        ];

        $this->editingId
            ? ChatAlias::findOrFail($this->editingId)->update($attributes)
            : ChatAlias::create($attributes + ['is_active' => true]);

        $this->showForm = false;
        session()->flash('chat-phrase-saved', 'Saved - the assistant will use it within a few minutes.');
    }

    public function toggleActive(int $id): void
    {
        $alias = ChatAlias::findOrFail($id);
        $alias->update(['is_active' => ! $alias->is_active]);
    }

    public function delete(int $id): void
    {
        ChatAlias::findOrFail($id)->delete();
    }

    public function render()
    {
        $aliases = ChatAlias::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('phrase', 'like', '%'.$this->search.'%')->orWhere('canonical', 'like', '%'.$this->search.'%')))
            ->orderByDesc('uses_count')
            ->orderBy('phrase')
            ->paginate(15);

        return view('livewire.superadmin-app.chat-phrases', [
            'aliases' => $aliases,
            'types' => ChatAlias::TYPES,
            'canonicalOptions' => ChatAlias::canonicalOptions($this->type),
        ])->layout('components.layouts.app', ['title' => 'Taught phrases']);
    }
}
