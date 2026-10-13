<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandlordRoleDefault extends Model
{
    protected $fillable = ['landlord_id', 'role', 'permissions'];

    protected $attributes = [
        'permissions' => '[]',
    ];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }
}
