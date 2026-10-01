<?php

namespace App\Livewire;

use App\Models\ChatTurn;
use App\Models\Setting;
use App\Services\ChatAliasService;
use App\Services\HouseMatchService;
use App\Services\HouseSearchAiService;
use App\Services\PlatformFaqService;
use App\Services\SupportContactService;
use App\Support\ChatCopy;
use App\Support\ChatLanguage;
use App\Support\ChatMasker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
 * lasts, without needing an account. Separately, each turn is logged
 * (masked) to chat_turns for the superadmin Chat insights dashboard.
 */
class ChatAssistant extends Component
{
    protected const SESSION_KEY = 'chat_assistant';

    // Cap what we keep in session so a very long conversation can't bloat the
    // session store indefinitely.
    protected const MAX_STORED_MESSAGES = 40;

    // How many "tell me what you want" replies in a row before we also offer
    // a human - the visitor is clearly not getting where they want to go.
    protected const CLARIFY_BEFORE_HANDOFF = 2;

    public bool $open = false;

    public string $input = '';

    public array $messages = [];

    public array $filters = [];

    // Which branch the previous turn's search landed on - lets this turn
    // deterministically interpret a short reply to the assistant's own last
    // question (e.g. "check other areas" answering "want me to check other
    // areas?") without depending on the LLM extraction noticing the same thing.
    public ?string $lastBranch = null;

    // The follow-up the assistant's last message offered ("want me to check
    // KES 14,000 instead?") - kept as data, not just as text in the reply, so a
    // bare "yes please" (or a tapped chip) can actually be carried out instead
    // of being re-run as the same failed search. Shape: ['type' => string,
    // 'patch' => filter changes a plain "yes" applies, or null when there are
    // several options and the visitor must pick one, 'chips' => the options
    // as quick replies].
    public ?array $pendingOffer = null;

    // Conversation bookkeeping that isn't search state: chatId (groups logged
    // turns), lang ('en'|'sw', what the visitor last wrote in), struggles
    // (consecutive turns we couldn't make progress on), lastTurnId (the logged
    // row an offer outcome is written back to).
    public array $meta = [];

    public bool $configured = true;

    public ?string $avatarUrl = null;

    // What this one reply() call is doing, for the turn log. Livewire doesn't
    // carry protected properties between requests, so it starts fresh each time.
    protected array $turn = [];

    public function mount(HouseSearchAiService $ai): void
    {
        $this->configured = $ai->isConfigured();

        $avatarPath = Setting::forLandlord(null)->payload['ai_avatar_path'] ?? null;
        $this->avatarUrl = $avatarPath ? Storage::disk('public')->url($avatarPath) : null;

        $stored = session(self::SESSION_KEY, []);
        $this->messages = $stored['messages'] ?? [];
        $this->filters = $stored['filters'] ?? [];
        $this->lastBranch = $stored['lastBranch'] ?? null;
        $this->pendingOffer = $stored['pendingOffer'] ?? null;
        $this->meta = $stored['meta'] ?? [];
        $this->meta['chatId'] ??= (string) Str::uuid();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open && empty($this->messages)) {
            $this->messages[] = [
                'role' => 'assistant',
                'text' => ChatCopy::t($this->configured ? 'greeting' : 'greeting_unconfigured'),
                'cards' => [],
            ];

            $this->persist();
        }
    }

    /**
     * Visitor tapped "use my location" and the browser handed back real
     * coordinates - these go straight into filters as a ready-made distance
     * point (see HouseMatchService::resolveDistancePoint()), no geocoding or
     * LLM extraction involved. A GPS point supersedes any previously
     * mentioned landmark, since it's a more precise version of the same
     * "rank by distance from a point" request.
     */
    public function useMyLocation(float $lat, float $lng): void
    {
        $this->filters['near_lat'] = $lat;
        $this->filters['near_lng'] = $lng;
        unset($this->filters['landmark']);

        $this->messages[] = ['role' => 'user', 'text' => 'Using my current location', 'cards' => []];
        $this->persist();

        $this->dispatch('chat-assistant-message-sent');
    }

    /** Browser denied/failed the geolocation prompt - say so instead of silently doing nothing. */
    public function locationDenied(): void
    {
        $this->messages[] = [
            'role' => 'assistant',
            'text' => ChatCopy::t('location_denied', $this->lang()),
            'cards' => [],
        ];
        $this->persist();
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

    /**
     * Visitor tapped a quick-reply chip under the assistant's last message.
     * Only the latest message's chips are rendered, so the index always refers
     * to a still-live offer. The chip's filter changes ride on the user bubble
     * ('apply') so reply() runs them exactly, with no free-text interpretation
     * in between.
     */
    public function pickChip(int $index): void
    {
        $last = end($this->messages);
        $chip = is_array($last) ? ($last['chips'][$index] ?? null) : null;

        if (! $chip) {
            return;
        }

        $this->messages[] = ['role' => 'user', 'text' => $chip['label'], 'cards' => [], 'apply' => $chip['patch']];
        $this->persist();

        $this->dispatch('chat-assistant-message-sent');
    }

    /** Slow turn: the actual OpenRouter round trip(s), run as its own request. */
    public function reply(HouseSearchAiService $ai, HouseMatchService $matcher): void
    {
        if (empty($this->messages) || end($this->messages)['role'] !== 'user') {
            return;
        }

        $lastMessage = end($this->messages);
        $lastUserText = $lastMessage['text'];
        $apply = $lastMessage['apply'] ?? null;

        $this->turn = [
            'text' => $lastUserText,
            'prior_offer' => (bool) $this->pendingOffer,
            'offer_result' => null,
            'used_fallback' => false,
            'llm_failed' => false,
        ];

        // A tapped chip's label is generated by us, so it says nothing about
        // what language the visitor writes in.
        if ($apply === null) {
            $this->meta['lang'] = ChatLanguage::detect($lastUserText) ?? ($this->meta['lang'] ?? 'en');
        }

        $lang = $this->lang();

        if (! $this->configured) {
            $this->respond(ChatCopy::t('unconfigured', $lang));

            return;
        }

        $rateLimitKey = 'chat-assistant:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $this->respond(ChatCopy::t('rate_limited', $lang));

            return;
        }

        RateLimiter::hit($rateLimitKey, 600);

        $aliases = app(ChatAliasService::class);

        // "Can I talk to someone?" is a request for a person, not a search -
        // hand over the team's contact links straight away. The search state
        // and any pending offer are kept, so the visitor can carry on after.
        if ($apply === null && $aliases->asksForHuman($lastUserText)) {
            $this->turn['prior_offer'] = false;
            $links = $this->contactLinks();

            $this->respond(
                ChatCopy::t($links ? 'handoff_requested' : 'handoff_unavailable', $lang),
                [], $this->pendingOffer['chips'] ?? [], $this->pendingOffer, $links, 'handoff',
            );

            return;
        }

        // A question about the platform itself ("how do you help property
        // managers", "what's the admission process") isn't a house search at
        // all - answer it directly from fixed, accurate copy instead of
        // forcing it through the search pipeline, where it would either fail
        // to extract anything and get a generic "tell me what you're looking
        // for", or - worse - get treated as a real query. Search state
        // (filters/lastBranch) is left untouched so a detour question doesn't
        // derail an in-progress search conversation.
        if ($faqAnswer = app(PlatformFaqService::class)->answer($lastUserText)) {
            $this->turn['prior_offer'] = false;
            $this->messages[] = ['role' => 'assistant', 'text' => $faqAnswer, 'cards' => []];
            $this->recordTurn($faqAnswer, 'faq', $this->pendingOffer, false);
            $this->persist();

            return;
        }

        // A tapped chip, or a plain "yes"/"no" to the assistant's own last offer,
        // is answered from the offer it refers to - never run back through
        // extraction, which only sees filters and would just repeat the search
        // that produced the offer (the "yes please" -> identical reply loop).
        $offer = $this->pendingOffer;

        if ($apply === null && $offer && $this->looksAffirmative($lastUserText)) {
            if ($offer['patch'] !== null) {
                $apply = $offer['patch'];
            } else {
                // Several options on the table - a bare "yes" doesn't say which.
                $this->respond(ChatCopy::t('which_one', $lang), [], $offer['chips'], $offer);

                return;
            }
        }

        if ($apply === null && $offer && $this->looksNegative($lastUserText)) {
            $this->turn['offer_result'] = 'declined';
            $this->respond(ChatCopy::t('declined', $lang));

            return;
        }

        if ($apply !== null) {
            $this->turn['offer_result'] = 'accepted';
            $this->filters = array_merge($this->filters, $apply);

            $this->runSearch($ai, $matcher, $lastUserText);

            return;
        }

        // A GPS point from useMyLocation() isn't part of extractFilters()'s own
        // JSON schema, so the LLM call below has no way to carry it forward -
        // it has to be preserved by hand, same idea as the regex overrides
        // just below. Except when the user just named a different place by
        // hand (a fresh landmark from this very extraction) - that's a more
        // specific request than an old location click and should win.
        $nearLat = $this->filters['near_lat'] ?? null;
        $nearLng = $this->filters['near_lng'] ?? null;
        $previousLandmark = $this->filters['landmark'] ?? null;

        // The exact GPS point has no business reaching a third-party LLM API
        // at all, even just as "previously known filters" context - strip it
        // before this call, not only from what the model is asked to return.
        $filtersForExtraction = array_diff_key($this->filters, array_flip(['near_lat', 'near_lng']));

        $extracted = $ai->extractFilters($this->historyForApi(), $filtersForExtraction);
        $this->turn['llm_failed'] = $extracted === null;
        $this->filters = $extracted ?? $this->filters;

        $newLandmark = $this->filters['landmark'] ?? null;
        if ($nearLat !== null && $newLandmark && $newLandmark !== $previousLandmark) {
            $nearLat = $nearLng = null;
        }

        if ($nearLat !== null && $nearLng !== null) {
            $this->filters['near_lat'] = $nearLat;
            $this->filters['near_lng'] = $nearLng;
        }

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
        $this->turn['used_fallback'] = $regexExtracted !== null;

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

        $this->runSearch($ai, $matcher, $lastUserText);
    }

    /**
     * Runs the current filters against real listings and appends the reply.
     * Shared by the normal path and the offer/chip path in reply().
     */
    protected function runSearch(HouseSearchAiService $ai, HouseMatchService $matcher, string $lastUserText): void
    {
        $lang = $this->lang();

        // A budget/amenity/nearby-only ask ("something with wifi under 15k") is
        // still a real, answerable search - HouseMatchService just runs it
        // unconstrained by type/area and lets the narrow/results branch handle
        // however many that matches. Requiring a type or place here too would
        // send it to "clarify" despite already having a real signal to search on.
        $hasEnoughToSearch = filled($this->filters['house_type'] ?? null)
            || filled($this->filters['area'] ?? null)
            || filled($this->filters['landmark'] ?? null)
            || filled($this->filters['property_name'] ?? null)
            || filled($this->filters['max_rent'] ?? null)
            || filled($this->filters['amenities'] ?? null)
            || filled($this->filters['nearby'] ?? null)
            || (filled($this->filters['near_lat'] ?? null) && filled($this->filters['near_lng'] ?? null));

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
            ? $ai->composeReply([['role' => 'user', 'content' => $lastUserText]], $result['facts'], $lang)
            : $ai->fallbackReply($result['facts'], $lang);

        $cards = $result['results']->isNotEmpty() ? $matcher->toCards($result['results']) : [];
        $offer = $this->offerFromFacts($result['facts']);
        $stuck = false;

        // Same words as the previous assistant message with nothing new to show
        // means the visitor is going round in circles - say so and change tack
        // rather than repeating the sentence a third time.
        $previous = collect($this->messages)->reverse()->firstWhere('role', 'assistant');
        if (empty($cards) && $previous && ($previous['text'] ?? null) === $reply) {
            $reply = ChatCopy::t('repeat', $lang);
            $stuck = true;
        }

        // Dead end with nothing concrete to offer, or several "what are you
        // looking for?" in a row: this is where a person can do better than us.
        if ($result['branch'] === 'none' && ! $offer) {
            $stuck = true;
        }

        if ($result['branch'] === 'clarify') {
            $this->meta['struggles'] = ($this->meta['struggles'] ?? 0) + 1;
            $stuck = $stuck || $this->meta['struggles'] >= self::CLARIFY_BEFORE_HANDOFF;
        } else {
            $this->meta['struggles'] = 0;
        }

        $links = $stuck ? $this->contactLinks() : [];

        if ($links) {
            $reply .= "\n\n".ChatCopy::t('handoff_intro', $lang);
        }

        $this->respond($reply, $cards, $offer['chips'] ?? [], $offer, $links, $result['branch']);
    }

    /** Contact buttons for the team, from Platform Settings; empty when none are configured. */
    protected function contactLinks(): array
    {
        return app(SupportContactService::class)->links($this->lang(), $this->filters);
    }

    /**
     * Turns a dead-end search into a concrete follow-up the visitor can accept.
     * Built from backend-computed facts only, so an offer is always something
     * that really exists (a real price, a real unit type).
     */
    protected function offerFromFacts(array $facts): ?array
    {
        $lang = $this->lang();
        $branch = $facts['branch'] ?? null;

        if ($branch === 'zero_results') {
            $patch = ['area_flexible' => true];

            return [
                'type' => 'widen_area',
                'patch' => $patch,
                'chips' => [['label' => ChatCopy::t('chip_other_areas', $lang), 'patch' => $patch]],
            ];
        }

        if ($branch !== 'none') {
            return null;
        }

        if (filled($facts['cheapest_available_for_type'] ?? null)) {
            $price = (int) $facts['cheapest_available_for_type'];
            $patch = ['max_rent' => $price];

            return [
                'type' => 'raise_budget',
                'patch' => $patch,
                'chips' => [['label' => ChatCopy::t('chip_show_price', $lang, ['price' => number_format($price)]), 'patch' => $patch]],
            ];
        }

        if (filled($facts['available_house_types'] ?? null)) {
            $chips = collect($facts['available_house_types'])
                ->take(4)
                ->map(fn (string $type) => ['label' => $type, 'patch' => ['house_type' => $type]])
                ->all();

            // Several options - a bare "yes" can't pick one, so no default patch.
            return ['type' => 'switch_type', 'patch' => null, 'chips' => $chips];
        }

        return null;
    }

    /** Appends an assistant message, records the follow-up it offers (if any), and logs the turn. */
    protected function respond(string $text, array $cards = [], array $chips = [], ?array $offer = null, array $links = [], ?string $branch = null): void
    {
        $this->pendingOffer = $offer;

        $this->messages[] = ['role' => 'assistant', 'text' => $text, 'cards' => $cards, 'chips' => $chips, 'links' => $links];

        $this->recordTurn($text, $branch, $offer, $links !== []);
        $this->persist();
    }

    /**
     * Logs this exchange for the superadmin Chat insights dashboard. Text is
     * masked first (phone numbers, emails, long digit runs) and the GPS point
     * is never stored. A failure here must never break the chat itself.
     */
    protected function recordTurn(string $replyText, ?string $branch, ?array $offer, bool $handoffShown): void
    {
        try {
            $this->resolvePreviousOffer();

            $row = ChatTurn::create([
                'chat_id' => $this->meta['chatId'],
                'user_text' => ChatMasker::mask(mb_substr($this->turn['text'] ?? '', 0, 500)),
                'reply_text' => mb_substr($replyText, 0, 1000),
                'language' => $this->lang(),
                'branch' => $branch,
                'filters' => array_diff_key($this->filters, array_flip(['near_lat', 'near_lng'])),
                'used_fallback' => $this->turn['used_fallback'] ?? false,
                'llm_failed' => $this->turn['llm_failed'] ?? false,
                'offer_type' => $offer['type'] ?? null,
                'handoff_shown' => $handoffShown,
            ]);

            $this->meta['lastTurnId'] = $row->id;
        } catch (\Throwable $e) {
            Log::warning('Chat turn logging failed', ['message' => $e->getMessage()]);
        }
    }

    /** What the visitor did with the previous turn's offer, written back to that logged row. */
    protected function resolvePreviousOffer(): void
    {
        if (empty($this->turn['prior_offer']) || empty($this->meta['lastTurnId'])) {
            return;
        }

        ChatTurn::where('id', $this->meta['lastTurnId'])
            ->where('chat_id', $this->meta['chatId'])
            ->update(['offer_result' => $this->turn['offer_result'] ?? 'ignored']);
    }

    protected function lang(): string
    {
        return $this->meta['lang'] ?? 'en';
    }

    /** A short, plainly affirmative reply - "yes", "ndio", "check other areas" - as opposed to a new, unrelated query. */
    protected function looksAffirmative(string $text): bool
    {
        if (app(ChatAliasService::class)->isYes($text)) {
            return true;
        }

        $normalized = trim(mb_strtolower($text), " \t\n\r\0\x0B.!?");

        if (mb_strlen($normalized) > 40) {
            return false;
        }

        return (bool) preg_match(
            '/^(yes|yeah|yep|yup|sure|ok|okay|please|go ahead|check other areas?|other areas?|check elsewhere|anywhere else|elsewhere|show (me )?other(s)?|widen|broaden)\b/i',
            $normalized
        );
    }

    /** A short, plainly negative reply to the assistant's own last offer. */
    protected function looksNegative(string $text): bool
    {
        if (app(ChatAliasService::class)->isNo($text)) {
            return true;
        }

        $normalized = trim(mb_strtolower($text), " \t\n\r\0\x0B.!?");

        return mb_strlen($normalized) <= 40
            && (bool) preg_match('/^(no|nope|nah|not really|no thanks|no thank you|never ?mind|cancel)\b/i', $normalized);
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
            'pendingOffer' => $this->pendingOffer,
            'meta' => $this->meta,
        ]);
    }

    public function render()
    {
        return view('livewire.chat-assistant');
    }
}
