<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HousePhoto extends Model
{
    protected $fillable = [
        'house_id',
        'path',
        'sort_order',
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function url(): string
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->path);
    }
}
