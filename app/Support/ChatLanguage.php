<?php

namespace App\Support;

/**
 * Cheap Swahili-vs-English guess for a single chat message. Returns null when
 * the message carries no signal ("ok", "1br", a bare number), so the caller can
 * keep whichever language the conversation was already in instead of flipping
 * on a short reply.
 */
class ChatLanguage
{
    protected const SWAHILI = [
        'nataka', 'ninataka', 'natafuta', 'ninatafuta', 'naomba', 'nyumba', 'chumba', 'vyumba',
        'kuna', 'bei', 'chini ya', 'bajeti', 'kodi', 'ndio', 'ndiyo', 'sawa', 'hapana', 'tafadhali',
        'asante', 'habari', 'mambo', 'niko', 'mtaa', 'eneo', 'mwezi', 'elfu', 'unaweza', 'wapi',
        'nipe', 'nipatie', 'ninaweza', 'naweza', 'sijui', 'poa', 'haya', 'karibu na', 'kwa mwezi',
        'bila', 'pamoja', 'nipigie', 'niongee', 'mtu', 'kuongea', 'ya kulala',
    ];

    protected const ENGLISH = [
        'the', 'a', 'an', 'is', 'are', 'can', 'could', 'get', 'want', 'looking', 'for', 'house',
        'find', 'under', 'below', 'please', 'yes', 'no', 'thanks', 'bedroom', 'with', 'near', 'rent',
        'any', 'have', 'do', 'you', 'what', 'how', 'in', 'my', 'me', 'i',
    ];

    public static function detect(string $text): ?string
    {
        $lower = ' '.mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text)).' ';
        $lower = preg_replace('/\s+/', ' ', $lower);

        $sw = $en = 0;

        foreach (self::SWAHILI as $word) {
            if (str_contains($lower, " {$word} ")) {
                $sw++;
            }
        }

        foreach (self::ENGLISH as $word) {
            if (str_contains($lower, " {$word} ")) {
                $en++;
            }
        }

        return match (true) {
            $sw > 0 && $sw >= $en => 'sw',
            $en > 0 => 'en',
            default => null,
        };
    }
}
