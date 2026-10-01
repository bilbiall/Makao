<?php

namespace App\Services;

use App\Models\ChatAlias;
use App\Models\House;
use Illuminate\Support\Facades\Cache;

/**
 * Local words the chat assistant should understand: a small built-in Swahili/
 * Sheng set, plus whatever the superadmin has added in the chat_aliases table
 * (cached, and cleared automatically whenever an alias is saved).
 */
class ChatAliasService
{
    protected const CACHE_KEY = 'chat_aliases_active';

    protected const BUILT_IN = [
        'yes' => ['ndio', 'ndiyo', 'sawa', 'haya', 'ewe', 'poa', 'naam', 'nakubali', 'sawa tu'],
        'no' => ['hapana', 'la', 'sitaki', 'siitaki', 'sio hivyo', 'hapana asante'],
        'human' => [
            'speak to someone', 'speak to a person', 'speak to a human', 'talk to someone', 'talk to a person',
            'talk to a human', 'real person', 'human', 'agent', 'customer care', 'customer service',
            'call me', 'contact you', 'contact us', 'whatsapp', 'phone number',
            'nipigie', 'niunganishe', 'niongee na mtu', 'nataka kuongea na mtu', 'naomba mtu', 'huduma kwa wateja',
        ],
    ];

    // Longest first, so "chumba kimoja cha kulala" wins over "chumba kimoja".
    protected const BUILT_IN_HOUSE_TYPES = [
        'chumba kimoja cha kulala' => '1 Bedroom',
        'vyumba viwili vya kulala' => '2 Bedroom',
        'vyumba vitatu vya kulala' => '3 Bedroom',
        'vyumba viwili' => '2 Bedroom',
        'vyumba vitatu' => '3 Bedroom',
        'vyumba vinne' => '4 Bedroom',
        'chumba kimoja' => 'Single Room',
        'bedsitta' => 'Bedsitter',
        'bedsit' => 'Bedsitter',
    ];

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Active aliases from the database, grouped by type. */
    protected function stored(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, 300, fn () => ChatAlias::where('is_active', true)
                ->get(['id', 'type', 'phrase', 'canonical'])
                ->groupBy('type')
                ->map(fn ($rows) => $rows->map(fn ($r) => ['id' => $r->id, 'phrase' => $r->phrase, 'canonical' => $r->canonical])->all())
                ->all());
        } catch (\Throwable) {
            // No table yet (fresh install/tests) - built-ins still work.
            return [];
        }
    }

    protected function phrases(string $type): array
    {
        return array_merge(
            self::BUILT_IN[$type] ?? [],
            array_column($this->stored()[$type] ?? [], 'phrase'),
        );
    }

    protected function contains(string $haystack, string $phrase): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])'.preg_quote(mb_strtolower($phrase), '/').'(?![\p{L}\p{N}])/u', $haystack);
    }

    /** Lower-cased, punctuation turned into spaces, so "Sawa, asante!" reads as "sawa asante". */
    protected function normalize(string $text): string
    {
        $text = preg_replace('/[.,!?;:]+/u', ' ', mb_strtolower($text));

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** A short reply that opens with one of the type's phrases - "ndio asante", "sawa". */
    protected function shortReplyIs(string $type, string $text): bool
    {
        $normalized = $this->normalize($text);

        if ($normalized === '' || mb_strlen($normalized) > 40) {
            return false;
        }

        foreach ($this->phrases($type) as $phrase) {
            if ($normalized === $phrase || str_starts_with($normalized, $phrase.' ')) {
                return true;
            }
        }

        return false;
    }

    public function isYes(string $text): bool
    {
        return $this->shortReplyIs('yes', $text);
    }

    public function isNo(string $text): bool
    {
        return $this->shortReplyIs('no', $text);
    }

    /** "Can I speak to someone" / "nipigie" - wants a person, not a search. */
    public function asksForHuman(string $text): bool
    {
        $lower = mb_strtolower($text);

        foreach ($this->phrases('human') as $phrase) {
            if ($this->contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /** The real area/city name behind a nickname the visitor used ("Kasa" -> "Kasarani"). */
    public function areaFor(string $text): ?string
    {
        return $this->canonicalFor('area', $text);
    }

    public function houseTypeFor(string $text): ?string
    {
        $lower = mb_strtolower($text);

        foreach (self::BUILT_IN_HOUSE_TYPES as $phrase => $type) {
            if ($this->contains($lower, $phrase)) {
                return $type;
            }
        }

        $type = $this->canonicalFor('house_type', $text);

        return in_array($type, House::UNIT_TYPES, true) ? $type : null;
    }

    protected function canonicalFor(string $type, string $text): ?string
    {
        $lower = mb_strtolower($text);

        // Longest phrase first so a more specific nickname beats a shorter one.
        $rows = collect($this->stored()[$type] ?? [])->sortByDesc(fn ($r) => mb_strlen($r['phrase']));

        foreach ($rows as $row) {
            if (filled($row['canonical']) && $this->contains($lower, $row['phrase'])) {
                try {
                    ChatAlias::whereKey($row['id'])->increment('uses_count');
                } catch (\Throwable) {
                }

                return $row['canonical'];
            }
        }

        return null;
    }
}
