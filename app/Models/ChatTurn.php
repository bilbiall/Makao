<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatTurn extends Model
{
    protected $fillable = [
        'chat_id', 'user_text', 'reply_text', 'language', 'branch', 'filters',
        'used_fallback', 'llm_failed', 'offer_type', 'offer_result',
        'handoff_shown', 'status',
    ];

    protected $casts = [
        'filters' => 'array',
        'used_fallback' => 'boolean',
        'llm_failed' => 'boolean',
        'handoff_shown' => 'boolean',
    ];

    /** Turns the assistant couldn't really answer - what the failure inbox lists. */
    public function scopeNeedsAttention($query)
    {
        return $query->whereNull('status')
            ->where(fn ($q) => $q->whereIn('branch', ['clarify', 'none', 'property_not_found'])->orWhere('handoff_shown', true));
    }
}
