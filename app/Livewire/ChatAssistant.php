<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Services\HouseMatchService;
use App\Services\HouseSearchAiService;
use App\Services\PlatformFaqService;
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

    // Which branch the previous turn's search landed on - lets this turn
    // deterministically interpret a short reply to the assistant's own last
    // question (e.g. "check other areas" answering "want me to check other
    // areas?") without depending on the LLM extraction noticing the same thing.
    public ?string $lastBranch = null;

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
        $this->lastBranch = $stored['lastBranch'] ?? null;
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open && empty($this->messages)) {
            $this->messages[] = [
                'role' => 'assistant',
                'text' => $this->configured
                    ? 'Hi! I\'m your Renty assistant, here to help you find your next home or short stay in Kenya. Tell me what you\'re looking for - e.g. "1 bedroom in Kasarani under 20k".'
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

        $lastUserText = end($this->messages)['text'];

        // A question about the platform itself ("how do you help property
        // managers", "what's the admission process") isn't a house search at
        // all - answer it directly from fixed, accurate copy instead of
        // forcing it through the search pipeline, where it would either fail
        // to extract anything and get a generic "tell me what you're looking
        // for", or - worse - get treated as a real query. Search state
        // (filters/lastBranch) is left untouched so a detour question doesn't
        // derail an in-progress search conversation.
        if ($faqAnswer = app(PlatformFaqService::class)->answer($lastUserText)) {
            $this->messages[] = ['role' => 'assistant', 'text' => $faqAnswer, 'cards' => []];
            $this->persist();

            return;
        }

        $extracted = $ai->extractFilters($this->historyForApi(), $this->filters);
        $this->filters = $extracted ?? $this->filters;

        // The regex/keyword net runs every turn, not only when extractFilters()
        // fails outright - a call can "succeed" (valid JSON, no error) while
        // still failing to notice something explicit in the text (e.g. it parsed
        // fine but left area/house_type null even though the user plainly typed
        // "in Nairobi"). Whatever it finds in THIS message always overrides -
        // a literal match in the user's current words beats a stale value
        // carried forward from an earlier turn (e.g. "actually, a bedsitter in
        // South B instead" must be able to replace an old "2 Bedroom in Kahawa
        // Sukari", not be silently ignored because that field was already set).
        $regexExtracted = $ai->extractFiltersFallback($lastUserText);

        foreach ($regexExtracted ?? [] as $field => $value) {
            $this->filters[$field] = $value;
        }

        // Deterministic handling of a short reply to the assistant's own last
        // question - "want me to check other areas?" -> "check other areas" (or
        // any other affirmative) must actually broaden the search, not repeat
        // the same question forever if the LLM/regex extraction misses that a
        // bare "yes"-shaped reply was answering it rather than a new query.
        if ($this->lastBranch === 'zero_results' && $this->looksAffirmative($lastUserText)) {
            $this->filters['area_flexible'] = true;
        }

        $hasEnoughToSearch = filled($this->filters['house_type'] ?? null) || filled($this->filters['area'] ?? null);

        $result = $hasEnoughToSearch
            ? $matcher->search($this->filters)
            : ['results' => collect(), 'branch' => 'clarify', 'facts' => ['branch' => 'clarify', 'filters' => $this->filters]];

        $this->lastBranch = $result['branch'];

        // Only let the LLM narrate when there's a real, checkable listing behind
        // it (branches 'results'/'narrow'/'alternatives_shown'). Every other
        // branch has no backend data to ground a sentence in, and a weak/free
        // model has been observed fabricating entire fake listings (wrong
        // currency, non-Kenyan areas, invented prices) rather than asking a
        // clarifying question when given nothing concrete - so those branches
        // always get the deterministic, hallucination-proof copy instead.
        $reply = $result['results']->isNotEmpty()
            ? $ai->composeReply([['role' => 'user', 'content' => $lastUserText]], $result['facts'])
            : $ai->fallbackReply($result['facts']);

        $this->messages[] = [
            'role' => 'assistant',
            'text' => $reply,
            'cards' => $result['results']->isNotEmpty() ? $matcher->toCards($result['results']) : [],
        ];

        $this->persist();
    }

    /** A short, plainly affirmative reply - "yes", "check other areas", "sure why not" - as opposed to a new, unrelated query. */
    protected function looksAffirmative(string $text): bool
    {
        $normalized = trim(mb_strtolower($text), " \t\n\r\0\x0B.!?");

        if (mb_strlen($normalized) > 40) {
            return false;
        }

        return (bool) preg_match(
            '/^(yes|yeah|yep|yup|sure|ok|okay|please|go ahead|check other areas?|other areas?|check elsewhere|anywhere else|elsewhere|show (me )?other(s)?|widen|broaden)\b/i',
            $normalized
        );
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
            'lastBranch' => $this->lastBranch,
        ]);
    }

    public function render()
    {
        return view('livewire.chat-assistant');
    }
}
