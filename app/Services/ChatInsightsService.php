<?php

namespace App\Services;

use App\Models\ChatTurn;

/**
 * The numbers behind the superadmin "Chat insights" - shared by the app-shell
 * page and the Filament widgets so both always agree.
 */
class ChatInsightsService
{
    public const DEAD_END_BRANCHES = ['none', 'clarify', 'property_not_found'];

    public const FOUND_BRANCHES = ['results', 'narrow', 'alternatives_shown'];

    public function stats(int $days = 30): array
    {
        $turns = fn () => ChatTurn::where('created_at', '>=', now()->subDays($days));

        $offers = fn () => $turns()->whereNotNull('offer_type')->whereNotNull('offer_result');

        return [
            'messages' => $turns()->count(),
            'conversations' => $turns()->distinct('chat_id')->count('chat_id'),
            'searches' => $turns()->whereIn('branch', array_merge(self::FOUND_BRANCHES, self::DEAD_END_BRANCHES, ['zero_results']))->count(),
            'found' => $turns()->whereIn('branch', self::FOUND_BRANCHES)->count(),
            'dead_ends' => $turns()->whereIn('branch', self::DEAD_END_BRANCHES)->count(),
            'swahili' => $turns()->where('language', 'sw')->count(),
            'handoffs' => $turns()->where('handoff_shown', true)->count(),
            'llm_failed' => $turns()->where('llm_failed', true)->count(),
            'offers_total' => $offers()->count(),
            'offers_accepted' => $offers()->where('offer_result', 'accepted')->count(),
        ];
    }

    /** @return array{labels: list<string>, all: list<int>, dead: list<int>} */
    public function daily(int $days = 30): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $rows = ChatTurn::where('created_at', '>=', $since)
            ->get(['created_at', 'branch'])
            ->groupBy(fn (ChatTurn $t) => $t->created_at->format('Y-m-d'));

        $range = collect(range(0, $days - 1))->map(fn (int $i) => $since->copy()->addDays($i));

        return [
            'labels' => $range->map(fn ($d) => $d->format('j M'))->all(),
            'all' => $range->map(fn ($d) => $rows->get($d->format('Y-m-d'))?->count() ?? 0)->all(),
            'dead' => $range->map(fn ($d) => $rows->get($d->format('Y-m-d'))?->whereIn('branch', self::DEAD_END_BRANCHES)->count() ?? 0)->all(),
        ];
    }

    /**
     * What visitors searched for that we had nothing to show - e.g. "123
     * searches under KES 10k for a 1 Bedroom, none available".
     *
     * @return list<array{type: string, place: string, searches: int, typical_budget: ?int}>
     */
    public function unmetDemand(int $days = 30, int $limit = 15): array
    {
        return ChatTurn::where('created_at', '>=', now()->subDays($days))
            ->whereIn('branch', ['none', 'zero_results'])
            ->get(['filters'])
            ->groupBy(function (ChatTurn $turn) {
                $f = $turn->filters ?? [];

                return ($f['house_type'] ?? 'Any type').' | '.($f['area'] ?? $f['landmark'] ?? 'Anywhere');
            })
            ->map(function ($turns, string $key) {
                [$type, $place] = explode(' | ', $key);
                $budgets = $turns->map(fn ($t) => (int) ($t->filters['max_rent'] ?? 0))->filter()->sort()->values();

                return [
                    'type' => $type,
                    'place' => $place,
                    'searches' => $turns->count(),
                    // The middle budget visitors tried, so one outlier doesn't skew it.
                    'typical_budget' => $budgets->isNotEmpty() ? $budgets->get((int) floor($budgets->count() / 2)) : null,
                ];
            })
            ->sortByDesc('searches')
            ->take($limit)
            ->values()
            ->all();
    }

    public static function percent(int $part, int $whole): string
    {
        return $whole > 0 ? round($part / $whole * 100).'%' : '-';
    }
}
