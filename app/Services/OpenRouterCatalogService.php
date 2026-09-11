<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches OpenRouter's public model catalog (no API key needed - this is a plain
 * GET, unlike the chat completions endpoint) so an admin can pick a real, currently
 * available free model instead of typing a slug blind. OpenRouter's free lineup
 * rotates over time - the app previously hardcoded a default that has since been
 * discontinued, and a stale/invalid slug fails every single chat-assistant call
 * silently (see HouseSearchAiService).
 */
class OpenRouterCatalogService
{
    protected const CACHE_KEY = 'openrouter:free_models';
    protected const CACHE_TTL_MINUTES = 60;

    /**
     * "openrouter/*" ids (openrouter/free, openrouter/auto, ...) are routers that
     * pick a DIFFERENT underlying model on every single call, not a pinned model -
     * excluded here because that per-call randomness is exactly what makes a
     * conversation inconsistent (fine one message, hallucinating or failing JSON
     * parsing the next, depending on which underlying model got picked that time).
     */
    protected const EXCLUDED_PREFIX = 'openrouter/';

    /**
     * @return array<string, string> model id => human-readable label, real pinned free models only
     */
    public function freeModels(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            try {
                $response = Http::timeout(10)->get('https://openrouter.ai/api/v1/models');

                if (! $response->successful()) {
                    Log::warning('Failed to fetch OpenRouter model catalog', ['status' => $response->status()]);

                    return [];
                }

                return collect($response->json('data', []))
                    ->filter(fn (array $model) => ($model['pricing']['prompt'] ?? null) === '0'
                        && ($model['pricing']['completion'] ?? null) === '0'
                        && ! str_starts_with($model['id'], self::EXCLUDED_PREFIX)
                        && in_array('text', $model['architecture']['output_modalities'] ?? [], true))
                    ->sortBy(fn (array $model) => $model['name'] ?? $model['id'])
                    ->mapWithKeys(fn (array $model) => [$model['id'] => $this->label($model)])
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch OpenRouter model catalog', ['message' => $e->getMessage()]);

                return [];
            }
        });
    }

    /** First entry of freeModels(), or null if the catalog couldn't be fetched. */
    public function firstFreeModel(): ?string
    {
        $models = $this->freeModels();

        return $models ? array_key_first($models) : null;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function label(array $model): string
    {
        $label = $model['name'] ?? $model['id'];

        if ($context = $model['context_length'] ?? null) {
            $label .= ' - ' . number_format($context) . ' ctx';
        }

        if (! in_array('response_format', $model['supported_parameters'] ?? [], true)) {
            $label .= ' (no JSON mode)';
        }

        return $label;
    }
}
