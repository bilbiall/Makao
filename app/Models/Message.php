<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

class Message extends Model
{
    use HasFactory, BelongsToLandlord;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'house_id',
        'issue_id',
        'parent_id',
        'body',
        'read_at',
        'landlord_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Stamped from the sender's landlord_id - broadcast messages have no house_id,
        // so this can't be derived via house the way other models derive it via tenant.
        static::creating(function ($message) {
            if (!$message->landlord_id && $message->sender_id) {
                $message->landlord_id = \App\Models\User::find($message->sender_id)?->landlord_id;
            }
        });
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
