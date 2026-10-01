<?php

namespace App\Models;

use App\Services\ChatAliasService;
use Illuminate\Database\Eloquent\Model;

class ChatAlias extends Model
{
    public const TYPES = [
        'yes' => 'Means "yes"',
        'no' => 'Means "no"',
        'area' => 'Area nickname',
        'house_type' => 'Unit type phrase',
        'human' => 'Asks for a person',
    ];

    /** The real values a phrase can point to - area/city names or unit types - so a typo can't create a dead alias. */
    public static function canonicalOptions(?string $type): array
    {
        return match ($type) {
            'area' => Area::pluck('name')->merge(City::pluck('name'))->filter()->unique()->sort()->mapWithKeys(fn ($n) => [$n => $n])->all(),
            'house_type' => array_combine(House::UNIT_TYPES, House::UNIT_TYPES),
            default => [],
        };
    }

    protected $fillable = ['type', 'phrase', 'canonical', 'language', 'is_active', 'uses_count'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        $forget = fn () => app(ChatAliasService::class)->forget();

        static::saved($forget);
        static::deleted($forget);
    }

    public function setPhraseAttribute(string $value): void
    {
        $this->attributes['phrase'] = mb_strtolower(trim($value));
    }
}
