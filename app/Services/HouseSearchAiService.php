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
    protected function apiKey(): ?string
    {
        return Setting::forLandlord(null)->payload['openrouter_api_key'] ?? null;
    }

    protected function model(): string
    {
        return Setting::forLandlord(null)->payload['openrouter_model'] ?? 'meta-llama/llama-3.1-8b-instruct:free';
    }

    public function isConfigured(): bool
    {
        $enabled = Setting::forLandlord(null)->payload['ai_search_enabled'] ?? true;

        return $enabled && filled($this->apiKey());
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
     * warm natural-language reply.
     */
    public function composeReply(array $history, array $facts): string
    {
        $system = <<<PROMPT
            You are Makao's friendly, concise rental-search assistant for Kenya. You are given verified search facts computed by the backend - never invent listings, prices, counts, or areas beyond what appears in the facts below. Reply in 2-4 short sentences, warm and conversational, no markdown, no bullet points.

            Facts: {$this->jsonEncode($facts)}

            Always mention the unit type and area from facts.filters when they're set (e.g. "2 Bedroom places in Westlands") so it's obvious what these results are for - this lets the user immediately spot it if you misunderstood them.

            Guidance per facts.branch:
            - "clarify": not enough info yet - ask what type of place (e.g. bedsitter, 1 bedroom) and which area they want.
            - "results": facts.sample lists what's shown as cards below your message - mention the count and invite them to tap one. If facts.unconfirmed_preferences is non-empty, briefly note those can't be verified yet.
            - "narrow": facts.count is large; you're only showing facts.sample as a taste - mention the total, and ask ONE clear narrowing question using facts.price_range or facts.house_types_available.
            - "zero_results": nothing matched in facts.filters.area - say so plainly, mention facts.alternative_areas (name and count), and ask if they want those instead or would rather wait.
            - "alternatives_shown": nothing in the originally requested area, but facts.sample is from other areas because the user agreed - note briefly that these are elsewhere.
            - "none": nothing matches at all, even ignoring area - say so honestly. If facts.cheapest_available_for_type is set, mention that's the actual lowest price available for that unit type right now and ask if they'd consider it. If facts.available_house_types is non-empty, mention which unit types genuinely are available instead. Otherwise just suggest loosening the budget or unit type.
            PROMPT;

        $raw = $this->chat(array_merge([['role' => 'system', 'content' => $system]], $history));

        return $raw ?: $this->fallbackReply($facts);
    }

    protected function fallbackReply(array $facts): string
    {
        $criteria = $this->describeFilters($facts['filters'] ?? []);

        return match ($facts['branch'] ?? null) {
            'results' => "Found {$facts['count']} place(s){$criteria} - take a look below.",
            'narrow' => "That matches {$facts['count']} places{$criteria} - here are a few. Want to narrow it down by budget or area?",
            'zero_results' => "No exact matches{$criteria} right now. Want me to check other areas?",
            'alternatives_shown' => "Nothing in that exact area, but here's what's available nearby.",
            'none' => $this->noneFallback($facts, $criteria),
            default => 'Tell me what you\'re looking for - e.g. "1 bedroom in Kasarani under 20k".',
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

        return match (true) {
            $type && $area => " for a {$type} in {$area}",
            (bool) $type => " for a {$type}",
            (bool) $area => " in {$area}",
            default => '',
        };
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
