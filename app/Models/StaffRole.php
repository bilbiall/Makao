<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffRole extends Model
{
    protected $fillable = [
        'landlord_id',
        'name',
        'scope_type',
        'permissions',
    ];

    // MySQL/MariaDB refuse a DEFAULT clause on JSON columns at the DB level
    // (error 1101), so the "start with no permissions granted" default lives
    // here instead - applies whenever a new instance doesn't set it explicitly.
    protected $attributes = [
        'permissions' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $slug): bool
    {
        return in_array($slug, $this->permissions ?? [], true);
    }
}
