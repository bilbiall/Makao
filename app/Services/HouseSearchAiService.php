<?php

namespace App\Services;

use App\Models\House;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to OpenRouter on behalf of the public chat assistant. Deliberately split
 * into two narrow jobs so the model never invents a listing: extractFilters()
 * only ever turns conversation text into a structured filter object (validated
 * against House's own enums), and composeReply() only ever narrates facts that
 * HouseMatchService already computed from the real database.
 */
class HouseSearchAiService
{
    public function __construct(protected OpenRouterCatalogService $catalog)
    {
    }

    protected function apiKey(): ?string
    {
        return Setting::forLandlord(null)->payload['openrouter_api_key'] ?? null;
    }

    /**
     * Falls back to the live catalog's first free model rather than a hardcoded
     * slug when the setting is blank - OpenRouter's free lineup rotates, and a
     * fixed fallback string here previously went stale (the model it named was
     * discontinued), causing every call to fail silently with no visible error.
     */
    protected function model(): string
    {
        $configured = Setting::forLandlord(null)->payload['openrouter_model'] ?? null;

        return $configured ?: ($this->catalog->firstFreeModel() ?? 'meta-llama/llama-3.1-8b-instruct:free');
    }

    /**
     * Requires a model to be set too, not just the API key - a blank model
     * previously fell through to a hardcoded default that silently rotted (see
     * model() above), so this now only reports "configured" when there's an
     * actual slug on file to use.
     */
    public function isConfigured(): bool
    {
        $payload = Setting::forLandlord(null)->payload ?? [];
        $enabled = $payload['ai_search_enabled'] ?? true;

        return $enabled && filled($this->apiKey()) && filled($payload['openrouter_model'] ?? null);
    }

    protected function chat(array $messages, bool $json = false): ?string
    {
        $apiKey = $this->apiKey();

        if (! $apiKey) {
            return null;
        }

        $payload = [
            'model' => $this->model(),
            'messages' => $messages,
            'max_tokens' => 400,
            'temperature' => 0.3,
        ];

        if ($json) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name'),
                ])
                ->timeout(25)
                ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            if (! $response->successful()) {
                Log::warning('OpenRouter chat request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');

            if (blank($content)) {
                Log::warning('OpenRouter chat request succeeded but returned no content', [
                    'body' => $response->body(),
                ]);

                return null;
            }

            // Some models emit their native tool-calling control tokens as plain
            // text when no `tools` schema was actually registered on the request
            // (e.g. "<|tool_call_start|>[search(...)]<|tool_call_end|>") - treat
            // that as a failed call rather than showing the raw leak to a visitor.
            if (str_contains($content, '<|')) {
                Log::warning('OpenRouter chat request returned leaked control tokens', [
                    'content' => mb_substr($content, 0, 500),
                ]);

                return null;
            }

            // Some models (reasoning-tuned free tiers especially) dump their raw
            // chain-of-thought into the same content field instead of a separate
            // reasoning field - "Here's a thinking process: 1. **Analyze User
            // Input:**..." - rather than the short final answer they were asked
            // for. Treat that as a failed call too instead of showing scratchpad
            // text to a visitor.
            $looksLikeReasoningTrace = preg_match(
                '/here.?s (a|my) (thinking|reasoning)|let me think|step[- ]by[- ]step|^step\s*\d+[:.]|analyze user input|chain.of.thought/i',
                $content
            ) || mb_strlen($content) > 600;

            if ($looksLikeReasoningTrace) {
                Log::warning('OpenRouter chat request returned a reasoning trace instead of a final answer', [
                    'content' => mb_substr($content, 0, 500),
                ]);

                return null;
            }

            return $content;
        } catch (\Throwable $e) {
            Log::warning('OpenRouter chat request threw', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Returns the full, updated filter set (not a diff) given the whole
     * conversation so far, or null if the call/parse failed - callers should
     * keep the previous filters unchanged in that case.
     */
    public function extractFilters(array $history, array $currentFilters): ?array
    {
        $unitTypes = $this->quotedList(House::UNIT_TYPES);
        $amenities = $this->quotedList(House::AMENITIES);
        $nearby = $this->quotedList(array_keys(House::NEARBY_CATEGORIES));

        $system = <<<PROMPT
            You extract structured search filters from a conversation between a visitor and Makao, a Kenyan rental-search assistant. Respond with ONLY a JSON object, no prose, no markdown fences, matching exactly this shape:

            {
              "area": string or null,
              "area_flexible": true, false, or null,
              "landmark": string or null,
              "property_name": string or null,
              "listing_mode": "long_term" or "short_term",
              "house_type": one of [{$unitTypes}] or null,
              "max_rent": integer or null,
              "amenities": array (zero or more of [{$amenities}]),
              "nearby": array (zero or more of [{$nearby}]),
              "unconfirmed_preferences": array of short phrases (e.g. "quiet area") for anything the user asked for that has no matching field above
            }

            Field meanings:
            - area: a Kenyan city/area/neighbourhood name exactly as the user said it (e.g. "Westlands", "Kasarani", "Mombasa"). null if never mentioned.
            - area_flexible: true once the user agrees to see other areas, false once they insist on only the named area, otherwise null.
            - landmark: a specific named place used as a proximity reference (e.g. "Yaya Centre", "JKIA", "Two Rivers Mall", "Nairobi CBD", a school or hospital name) - NOT a neighbourhood/area name, which always goes in "area" instead. Only set this when the user is describing closeness to a specific place ("near", "close to", "walking distance from"), not just naming where they want to live. null if not mentioned.
            - property_name: the specific named building/property/complex the user is asking about (e.g. "Dakota Apartments", "Kilimani Breeze", "Westlands Vista") - NOT a neighbourhood/area name (that goes in "area") and NOT a landmark used only as a proximity reference. Set this whenever the user names a specific property, asks "is there vacancy in X", "any units at X", etc. null if not mentioned.
            - listing_mode: "short_term" only for BnB/nightly/short-stay/airbnb-style requests, otherwise "long_term". Default "long_term" when unclear.
            - house_type: must be an exact string from the allowed list above (e.g. "1 Bedroom", "Bedsitter") - map phrasing like "one bedroom" or "1br" to "1 Bedroom". null if not mentioned.
            - max_rent: convert phrasing like "20k", "under 20,000", "less than 20k" to a plain integer (KES per month). null if not mentioned.
            - amenities / nearby: only use the exact strings from the allowed lists above - never invent new ones.
            - unconfirmed_preferences: anything the user asked for that has no matching field (e.g. "quiet", "safe area") goes here as a short phrase instead of being mapped to a field - carry these forward too unless the user drops them.

            Always carry forward a previously known value the user hasn't contradicted or changed.

            Previously known filters: {$this->jsonEncode($currentFilters)}
            PROMPT;

        $raw = $this->chat(
            array_merge([['role' => 'system', 'content' => $system]], $history),
            json: true
        );

        if (! $raw) {
            return null;
        }

        $parsed = $this->parseJson($raw);

        if ($parsed === null) {
            Log::warning('OpenRouter filter extraction returned unparseable JSON - keeping previous filters', [
                'raw' => mb_substr($raw, 0, 500),
            ]);
        }

        return $parsed;
    }

    /**
     * Turns backend-computed facts (never raw model guesses) into a short,
     * warm natural-language reply. Callers must only invoke this when
     * `facts.sample` actually has real listings behind it (see
     * ChatAssistant::reply()) - a weak model given nothing concrete to narrate
     * has been observed fabricating entire listings (fake areas, fake KES/$
     * prices) instead of admitting it has nothing, so this method is not a
     * substitute for that check, only a second line of defense.
     */
    public function composeReply(array $history, array $facts): string
    {
        $system = <<<PROMPT
            You are Makao's friendly, concise rental-search assistant for Kenya. You are given verified search facts computed by the backend - never invent listings, prices, counts, or areas beyond what appears in the facts below. Reply in 2-4 short sentences, warm and conversational, no markdown, no bullet points.

            Facts: {$this->jsonEncode($facts)}

            Critical rules - breaking any of these is worse than a short or incomplete-sounding reply:
            - The search described in the facts has ALREADY happened. Never say you are about to search, "let me check", "searching now", or ask the user to wait - speak only in the present/past tense about the facts you were given.
            - Never state a price, area name, or listing detail that does not literally appear in facts.sample or facts.filters. If you are unsure of a number, omit it rather than guess.
            - All prices are Kenyan Shillings - always write "KES", never "$" or any other currency.
            - Always finish your sentences - never cut off mid-thought.
            - Output ONLY the final 2-4 sentence answer, nothing else. Never show your reasoning, thinking process, or step-by-step analysis, and never use headers like "Step 1" or "Here's my thinking process" - the user must never see how you arrived at the answer, only the answer itself.

            Always mention the unit type and area (or landmark, or "near you" if facts.filters.near_me is true) from facts.filters when they're set (e.g. "2 Bedroom places in Westlands", "places near Yaya Centre", "places near you") so it's obvious what these results are for - this lets the user immediately spot it if you misunderstood them. When facts.filters.landmark or facts.filters.near_me is set, results are sorted by real distance from that point - a sample entry with a distance_km field is genuinely that many km from it (round to one decimal, e.g. "1.2km"); never state a distance for an entry that has no distance_km. Never guess or imply an actual place name for a near_me search - you were not told where the visitor is, only that results are sorted by distance from it.

            Guidance per facts.branch:
            - "clarify": not enough info yet - ask what type of place (e.g. bedsitter, 1 bedroom) and which area they want.
            - "results": facts.sample lists what's shown as cards below your message - mention the count and invite them to tap one. If facts.unconfirmed_preferences is non-empty, briefly note those can't be verified yet.
            - "narrow": facts.count is large; you're only showing facts.sample as a taste - mention the total, and ask ONE clear narrowing question using facts.price_range or facts.house_types_available.
            - "zero_results": nothing matched in facts.filters.area - say so plainly, mention facts.alternative_areas (name and count), and ask if they want those instead or would rather wait.
            - "alternatives_shown": nothing in the originally requested area, but facts.sample is from other areas because the user agreed - note briefly that these are elsewhere.
            - "none": nothing matches at all, even ignoring area - say so honestly. If facts.cheapest_available_for_type is set, mention that's the actual lowest price available for that unit type right now and ask if they'd consider it. If facts.available_house_types is non-empty, mention which unit types genuinely are available instead. Otherwise just suggest loosening the budget or unit type.
            - "property_not_found": the user asked about a specific named property (facts.requested_property_name) that doesn't exist in the database at all - say plainly you couldn't find a property by that name (quote it back), and ask if they'd like to search by area or budget instead. Never guess at what property they might have meant, and never fall back to talking about a different unit type or price - that would answer a question they didn't ask.
            PROMPT;

        $raw = $this->chat(array_merge([['role' => 'system', 'content' => $system]], $history));

        return $raw ?: $this->fallbackReply($facts);
    }

    /**
     * Deterministic, hallucination-proof narration - used both as the
     * composeReply() failure path and, deliberately, as the ONLY narration for
     * any branch with no real listings behind it (see ChatAssistant::reply()).
     */
    public function fallbackReply(array $facts): string
    {
        $criteria = $this->describeFilters($facts['filters'] ?? []);

        return match ($facts['branch'] ?? null) {
            'results' => "Found {$facts['count']} place(s){$criteria} - take a look below.",
            'narrow' => "That matches {$facts['count']} places{$criteria} - here are a few. Want to narrow it down by budget or area?",
            'zero_results' => "No exact matches{$criteria} right now. Want me to check other areas?",
            'alternatives_shown' => "Nothing in that exact area, but here's what's available nearby.",
            'none' => $this->noneFallback($facts, $criteria),
            'property_not_found' => "I couldn't find a property called \"{$facts['requested_property_name']}\". Want me to search by area or budget instead?",
            default => 'What type of house are you looking for - bedsitter, 1 bedroom, 2 bedroom...? Tell me the area and your budget too, e.g. "1 bedroom in Kasarani under 20k".',
        };
    }

    protected function noneFallback(array $facts, string $criteria): string
    {
        if (filled($facts['cheapest_available_for_type'] ?? null)) {
            $price = number_format($facts['cheapest_available_for_type']);

            return "I couldn't find anything matching that{$criteria}. The cheapest one actually available right now is around KES {$price} - want me to check that instead?";
        }

        if (filled($facts['available_house_types'] ?? null)) {
            $types = implode(', ', $facts['available_house_types']);

            return "I couldn't find anything matching that{$criteria}. What is available right now: {$types}. Want to try one of those?";
        }

        return "I couldn't find anything matching that{$criteria}. Try loosening the budget or unit type.";
    }

    /**
     * Renders the filters actually searched as a short " for a 2 Bedroom in
     * Westlands"-style phrase, so even the deterministic fallback (used
     * whenever the narration call itself fails) tells the user what was
     * searched - making it obvious when extraction misread their request
     * instead of silently showing mismatched results under vague text.
     */
    protected function describeFilters(array $filters): string
    {
        $type = $filters['house_type'] ?? null;
        $area = $filters['area'] ?? null;
        $landmark = $filters['landmark'] ?? null;
        $propertyName = $filters['property_name'] ?? null;
        $nearMe = $filters['near_me'] ?? false;

        // A named property is the most specific thing the user could have
        // asked for, so it wins over area/landmark/near_me when more than one
        // is set - same priority HouseMatchService gives it everywhere else.
        $place = match (true) {
            (bool) $propertyName => "at {$propertyName}",
            (bool) $area => "in {$area}",
            (bool) $landmark => "near {$landmark}",
            (bool) $nearMe => 'near you',
            default => null,
        };

        return match (true) {
            $type && $place => " for a {$type} {$place}",
            (bool) $type => " for a {$type}",
            (bool) $place => " {$place}",
            default => '',
        };
    }

    /**
     * Lightweight keyword/regex extraction against the CURRENT message only -
     * a pure "what does this text literally say" detector, not a merge with
     * prior turns (the caller owns merging - see ChatAssistant::reply()).
     * Matches unit types against House::UNIT_TYPES and areas/cities against
     * the real Area/City tables so a plain, literal query like "2 bedroom in
     * Kahawa Sukari" still triggers a real search even when the LLM-based
     * extractFilters() call fails outright (no API key, provider error) or
     * "succeeds" but silently misses something explicit in the text (observed
     * with the configured free model). Returns only the fields actually found
     * in $text, or null if nothing recognizable was found - the caller
     * applies each found field as an override, since a literal match in the
     * user's own current message is a stronger signal than whatever's already
     * carried forward from earlier turns.
     */
    /**
     * Word-number spellings for house_type ("two bedroom") - only up to four
     * since "5 Bedroom" and beyond isn't a real House::UNIT_TYPES option.
     */
    protected const WORD_NUMBERS = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4];

    /**
     * Keyword => canonical House::AMENITIES value. Deliberately not exhaustive -
     * only the amenities visitors actually phrase as a short, unambiguous keyword
     * in a chat message (a longer tail here just adds false-positive risk for
     * little gain, since a missed amenity only means it isn't used as a filter -
     * it never causes an invented fact).
     */
    protected const AMENITY_KEYWORDS = [
        'borehole' => 'Borehole water',
        'generator' => 'Backup generator',
        'parking' => 'Secure parking',
        'cctv' => 'CCTV',
        'electric fence' => 'Electric fence',
        'wifi' => 'Wi-Fi',
        'wi-fi' => 'Wi-Fi',
        'balcony' => 'Balcony',
        'lift' => 'Lift',
        'elevator' => 'Lift',
        'ensuite' => 'Master ensuite',
        'en-suite' => 'Master ensuite',
        'gym' => 'Gym',
        'swimming pool' => 'Swimming pool',
        'dsq' => 'DSQ (servant quarter)',
        'servant quarter' => 'DSQ (servant quarter)',
        'garden' => 'Garden',
        'pet friendly' => 'Pet friendly',
        'furnished' => 'Furnished',
        'air conditioning' => 'Air conditioning',
        'aircon' => 'Air conditioning',
        'dstv' => 'DSTV/Netflix ready',
    ];

    /**
     * Keyword => House::NEARBY_CATEGORIES slug. Only fires when paired with an
     * explicit proximity phrase (see extractFiltersFallback()) - the bare word
     * "school"/"market" alone is too common in casual phrasing to safely imply
     * "must be near one", since this becomes a hard database filter.
     */
    protected const NEARBY_KEYWORDS = [
        'school' => 'school',
        'hospital' => 'hospital',
        'clinic' => 'hospital',
        'mall' => 'mall',
        'supermarket' => 'supermarket',
        'market' => 'market',
        'matatu' => 'bus_stage',
        'bus stage' => 'bus_stage',
        'highway' => 'main_road',
        'main road' => 'main_road',
        'tarmac' => 'main_road',
        'church' => 'place_of_worship',
        'mosque' => 'place_of_worship',
        'bank' => 'bank_atm',
        'atm' => 'bank_atm',
        'police' => 'police_station',
    ];

    public function extractFiltersFallback(string $text): ?array
    {
        $filters = [];
        $lower = mb_strtolower($text);

        if ($houseType = $this->extractHouseTypeFallback($text, $lower)) {
            $filters['house_type'] = $houseType;
        }

        // Longest name first so "Kahawa Sukari" wins over a shorter partial
        // match like "Kahawa" - geo_id (what House::inAreaOrCity matches on)
        // mirrors Area.name exactly, so the real cased value is used as-is.
        $names = \App\Models\Area::pluck('name')
            ->merge(\App\Models\City::pluck('name'))
            ->filter()
            ->unique()
            ->sortByDesc(fn ($name) => mb_strlen($name));

        foreach ($names as $name) {
            if (mb_stripos($text, $name) !== false) {
                $filters['area'] = $name;
                break;
            }
        }

        // A misspelled area ("Westland" for "Westlands", "Kilimanii" for
        // "Kilimani") would otherwise silently fail to match at all and drop
        // the visitor straight into "clarify" - only tried once an exact
        // substring match has already failed, so a correct spelling never
        // gets second-guessed by a coincidentally-close name elsewhere in the
        // same list.
        if (! isset($filters['area']) && ($fuzzy = $this->fuzzyFindInList($text, $names))) {
            $filters['area'] = $fuzzy;
        }

        // Same idea, against real property names - lets a literal "is there
        // vacancy in Dakota Apartments" still trigger a real property-name
        // search even when the LLM call fails or misses it (see class docblock).
        $propertyNames = \App\Models\Location::pluck('location_name')
            ->filter()
            ->unique()
            ->sortByDesc(fn ($name) => mb_strlen($name));

        foreach ($propertyNames as $name) {
            if (mb_stripos($text, $name) !== false) {
                $filters['property_name'] = $name;
                break;
            }
        }

        if (! isset($filters['property_name']) && ($fuzzy = $this->fuzzyFindInList($text, $propertyNames))) {
            $filters['property_name'] = $fuzzy;
        }

        // A named property or area already anchors the search - a landmark on
        // top of one of those would just be redundant (or, worse, contradict
        // it), so this generic "near <proper noun>" capture only fires when
        // neither is already set. Deliberately conservative (a capitalized
        // phrase right after the proximity word, cut at punctuation/a stop
        // word) since an over-eager capture here would hand LandmarkGeocoder
        // pure noise - harmless (it just fails to geocode), but pointless.
        if (empty($filters['area']) && empty($filters['property_name'])
            && preg_match('/\b(?:near|close to|next to|around)\s+([A-Z][\w\'-]*(?:\s+[A-Z]?[\w\'-]*){0,3})/', $text, $m)) {
            $landmark = trim(preg_replace('/\s+(?:in|for|under|and|with)\b.*/i', '', $m[1]));
            if ($landmark !== '' && mb_strlen($landmark) <= 40) {
                $filters['landmark'] = $landmark;
            }
        }

        if (preg_match('/\b(bnb|airbnb|air bnb|short.?stay|short.?term|nightly|per night|vacation rental)\b/i', $text)) {
            $filters['listing_mode'] = 'short_term';
        }

        $amenities = [];
        foreach (self::AMENITY_KEYWORDS as $keyword => $amenity) {
            if (str_contains($lower, $keyword)) {
                $amenities[] = $amenity;
            }
        }
        if ($amenities) {
            $filters['amenities'] = array_values(array_unique($amenities));
        }

        $isProximityPhrase = (bool) preg_match('/\b(near|close to|next to|walking distance|around)\b/i', $lower);
        if ($isProximityPhrase) {
            $nearby = [];
            foreach (self::NEARBY_KEYWORDS as $keyword => $slug) {
                if (str_contains($lower, $keyword)) {
                    $nearby[] = $slug;
                }
            }
            if ($nearby) {
                $filters['nearby'] = array_values(array_unique($nearby));
            }
        }

        if (preg_match('/\b(?:kshs?|kes)\.?\s?(\d[\d,]*)\b/i', $text, $m)) {
            $filters['max_rent'] = (int) str_replace(',', '', $m[1]);
        } elseif (preg_match('/(\d[\d,]*)\s*k\b/i', $text, $m)) {
            $filters['max_rent'] = (int) str_replace(',', '', $m[1]) * 1000;
        } elseif (preg_match('/\b(\d{4,6})\b/', str_replace(',', '', $text), $m)) {
            $filters['max_rent'] = (int) $m[1];
        }

        return $filters ?: null;
    }

    /**
     * Recognizes far more phrasings of a unit type than a single regex could
     * read cleanly: digit or spelled-out bedroom counts ("2 bed", "2br", "two
     * bedroom"), plus every other House::UNIT_TYPES value that has a common
     * one-or-two-word name.
     */
    protected function extractHouseTypeFallback(string $text, string $lower): ?string
    {
        if (preg_match('/(\d+)\s*-?\s*(?:bed(?:room)?s?|br)\b/i', $text, $m)) {
            $type = $m[1] . ' Bedroom';

            return in_array($type, House::UNIT_TYPES, true) ? $type : null;
        }

        foreach (self::WORD_NUMBERS as $word => $number) {
            if (preg_match('/\b' . $word . '\s*-?\s*(?:bed(?:room)?s?|br)\b/i', $text)) {
                return $number . ' Bedroom';
            }
        }

        $exact = match (true) {
            str_contains($lower, 'bedsitter'), str_contains($lower, 'bed sitter') => 'Bedsitter',
            str_contains($lower, 'studio') => 'Studio',
            str_contains($lower, 'single room') => 'Single Room',
            str_contains($lower, 'maisonette') => 'Maisonette',
            str_contains($lower, 'town house'), str_contains($lower, 'townhouse') => 'Townhouse',
            str_contains($lower, 'own compound') => 'Own Compound',
            default => null,
        };

        // Tolerates a typo'd unit type ("bedsiter", "studoi", "maisonete") the
        // same way area/property names do below.
        return $exact ?? $this->fuzzyFindInList($text, ['Bedsitter', 'Studio', 'Single Room', 'Maisonette', 'Townhouse', 'Own Compound']);
    }

    /**
     * Tolerates common typos by comparing each candidate against every
     * same-word-count window of the message ("Westland" vs "Westlands",
     * "Kilimanii" vs "Kilimani") - only meant as a second pass after an exact
     * substring match has already failed. A missed typo just means the
     * clarify fallback asks again (no invented fact); a wrongly-fuzzy-matched
     * name would search the wrong thing, so the distance threshold stays
     * tight (roughly one edit per four characters, minimum one).
     */
    protected function fuzzyFindInList(string $text, iterable $candidates): ?string
    {
        $words = preg_split('/\s+/', trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text)));
        $wordCount = count($words);

        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($candidates as $candidate) {
            $candidateWords = preg_split('/\s+/', trim($candidate));
            $n = count($candidateWords);

            if ($wordCount < $n) {
                continue;
            }

            $threshold = max(1, (int) floor(mb_strlen($candidate) / 4));

            for ($i = 0; $i <= $wordCount - $n; $i++) {
                $window = implode(' ', array_slice($words, $i, $n));
                $distance = $this->editDistance(mb_strtolower($window), mb_strtolower($candidate));

                if ($distance > 0 && $distance <= $threshold && $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $best = $candidate;
                }
            }
        }

        return $best;
    }

    /**
     * Edit distance that also counts an adjacent-letter swap ("studoi" vs
     * "studio") as a single edit, not two - plain levenshtein() treats a
     * transposition as two substitutions, which is stricter than a typo this
     * common deserves. Fine to run per-candidate here: every name involved is
     * short (a Kenyan area/unit-type name, not a paragraph).
     */
    protected function editDistance(string $a, string $b): int
    {
        $a = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
        $b = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);
        $lenA = count($a);
        $lenB = count($b);

        $d = [];
        for ($i = 0; $i <= $lenA; $i++) {
            $d[$i][0] = $i;
        }
        for ($j = 0; $j <= $lenB; $j++) {
            $d[0][$j] = $j;
        }

        for ($i = 1; $i <= $lenA; $i++) {
            for ($j = 1; $j <= $lenB; $j++) {
                $cost = ($a[$i - 1] === $b[$j - 1]) ? 0 : 1;

                $d[$i][$j] = min(
                    $d[$i - 1][$j] + 1,
                    $d[$i][$j - 1] + 1,
                    $d[$i - 1][$j - 1] + $cost
                );

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $d[$lenA][$lenB];
    }

    protected function parseJson(string $raw): ?array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(json)?/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        $decoded = json_decode(trim($raw), true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function quotedList(array $items): string
    {
        return implode(', ', array_map(fn ($item) => '"'.$item.'"', $items));
    }

    protected function jsonEncode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }
}
