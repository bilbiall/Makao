<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Services\HouseMatchService;
use App\Services\HouseSearchAiService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Floating "find me a place" chat bubble on the public site. Each turn: (1)
 * HouseSearchAiService::extractFilters() turns the conversation into a
 * structured filter set, (2) HouseMatchService::search() runs that against
 * real listings and decides a branch (show results / ask to narrow / offer
 * alternatives), (3) HouseSearchAiService::composeReply() narrates only the
 * facts the matcher computed. The model never sees or invents raw listings.
 *
 * send() and reply() are deliberately two separate requests: send() only
 * appends the user's bubble and clears the input, so it comes back fast and
 * Livewire re-renders immediately - reply() then makes the slow OpenRouter
 * round trip in a second request while the typing indicator shows. Doing both
 * in one method meant the user's own message stayed invisible (and the input
 * stayed full) until the AI reply was ready, since Livewire only re-renders
 * once a request completes.
 *
 * Messages persist to the session (not a DB table), so anonymous visitors
 * keep their conversation across page loads for as long as their session
 * lasts, without needing an account.
 */
class ChatAssistant extends Component
{
    protected const SESSION_KEY = 'chat_assistant';

    // Cap what we keep in session so a very long conversation can't bloat the
    // session store indefinitely.
    protected const MAX_STORED_MESSAGES = 40;

    public bool $open = false;

    public string $input = '';

    public array $messages = [];

    public array $filters = [];

    public bool $configured = true;

    public ?string $avatarUrl = null;

    public function mount(HouseSearchAiService $ai): void
    {
        $this->configured = $ai->isConfigured();

        $avatarPath = Setting::forLandlord(null)->payload['ai_avatar_path'] ?? null;
        $this->avatarUrl = $avatarPath ? Storage::disk('public')->url($avatarPath) : null;

        $stored = session(self::SESSION_KEY, []);
        $this->messages = $stored['messages'] ?? [];
        $this->filters = $stored['filters'] ?? [];
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open && empty($this->messages)) {
            $this->messages[] = [
                'role' => 'assistant',
                'text' => $this->configured
                    ? 'Hi! Tell me what kind of place you\'re looking for - e.g. "1 bedroom in Kasarani under 20k".'
                    : "Hi! The chat assistant isn't set up yet, but you can browse listings directly using search.",
                'cards' => [],
            ];

            $this->persist();
        }
    }

    /** Fast turn: just show what the user typed, then hand off to reply(). */
    public function send(): void
    {
        $text = trim($this->input);
        $this->input = '';

        if ($text === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'text' => mb_substr($text, 0, 500), 'cards' => []];
        $this->persist();

        $this->dispatch('chat-assistant-message-sent');
    }

    /** Slow turn: the actual OpenRouter round trip(s), run as its own request. */
    public function reply(HouseSearchAiService $ai, HouseMatchService $matcher): void
    {
        if (empty($this->messages) || end($this->messages)['role'] !== 'user') {
            return;
        }

        if (! $this->configured) {
            $this->messages[] = [
                'role' => 'assistant',
                'text' => "The chat assistant isn't set up yet - please browse listings directly for now.",
                'cards' => [],
            ];
            $this->persist();

            return;
        }

        $rateLimitKey = 'chat-assistant:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $this->messages[] = [
                'role' => 'assistant',
                'text' => "You've sent quite a few messages - please wait a few minutes before continuing.",
                'cards' => [],
            ];
            $this->persist();

            return;
        }

        RateLimiter::hit($rateLimitKey, 600);

        $extracted = $ai->extractFilters($this->historyForApi(), $this->filters);

        if ($extracted) {
            $this->filters = $extracted;
        }

        $hasEnoughToSearch = filled($this->filters['house_type'] ?? null) || filled($this->filters['area'] ?? null);

        $result = $hasEnoughToSearch
            ? $matcher->search($this->filters)
            : ['results' => collect(), 'branch' => 'clarify', 'facts' => ['branch' => 'clarify', 'filters' => $this->filters]];

        $reply = $ai->composeReply($this->historyForApi(), $result['facts']);

        $this->messages[] = [
            'role' => 'assistant',
            'text' => $reply,
            'cards' => $result['results']->isNotEmpty() ? $matcher->toCards($result['results']) : [],
        ];

        $this->persist();
    }

    /** OpenRouter's chat format only knows role+content - drop our extra 'cards' key. */
    protected function historyForApi(): array
    {
        return collect($this->messages)
            ->map(fn (array $m) => ['role' => $m['role'], 'content' => $m['text']])
            ->all();
    }

    protected function persist(): void
    {
        session()->put(self::SESSION_KEY, [
            'messages' => array_slice($this->messages, -self::MAX_STORED_MESSAGES),
            'filters' => $this->filters,
        ]);
    }

    public function render()
    {
        return view('livewire.chat-assistant');
    }
}
