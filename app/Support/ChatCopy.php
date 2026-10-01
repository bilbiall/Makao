<?php

namespace App\Support;

/**
 * Fixed, hand-written wording for every canned chat reply, in English and
 * Swahili. These never come from the model, which is what keeps the assistant
 * from inventing listings in either language.
 */
class ChatCopy
{
    protected const STRINGS = [
        'en' => [
            'greeting' => 'Hi! I\'m your Renty assistant. What type of house are you looking for - bedsitter, 1 bedroom, 2 bedroom...? Tell me the area and your budget too, e.g. "1 bedroom in Kasarani under 20k", and I\'ll find it. Unaweza pia kuandika kwa Kiswahili.',
            'greeting_unconfigured' => "Hi! The chat assistant isn't set up yet, but you can browse listings directly using search.",
            'unconfigured' => "The chat assistant isn't set up yet - please browse listings directly for now.",
            'rate_limited' => "You've sent quite a few messages - please wait a few minutes before continuing.",
            'location_denied' => "I couldn't get your location - you can still tell me an area or a landmark instead.",
            'which_one' => 'Which one would you like? Tap an option below, or tell me the unit type.',
            'declined' => 'No problem - what would you like to change? You can try a different area, unit type or budget.',
            'repeat' => "I'm still not finding a match for that. Could you try a different area, unit type or budget? You can also browse all listings directly from the Rent Long-Term page.",
            'handoff_intro' => "I may not be the best help with this one - our team can take it from here:",
            'handoff_requested' => 'Of course - you can reach our team directly here:',
            'handoff_unavailable' => "I can't connect you to the team from here yet, but you can use the Contact page, or keep searching with me.",
            'link_whatsapp' => 'Chat on WhatsApp',
            'link_call' => 'Call us',
            'link_email' => 'Email us',
            'handoff_message' => 'Hi, I need help finding a place on :app.',
            'handoff_looking_for' => ' I was looking for: :summary.',
            'chip_other_areas' => 'Check other areas',
            'chip_show_price' => 'Show KES :price homes',
        ],
        'sw' => [
            'greeting' => 'Habari! Mimi ni msaidizi wako wa Renty. Unatafuta nyumba ya aina gani - bedsitter, chumba kimoja, vyumba viwili...? Niambie pia eneo na bajeti, mfano "chumba kimoja Kasarani chini ya 20k", nitakutafutia.',
            'greeting_unconfigured' => 'Habari! Msaidizi wa mazungumzo bado haujawekwa, lakini unaweza kuangalia nyumba moja kwa moja kwa kutumia utafutaji.',
            'unconfigured' => 'Msaidizi wa mazungumzo bado haujawekwa - tafadhali angalia nyumba moja kwa moja kwa sasa.',
            'rate_limited' => 'Umetuma ujumbe mwingi - tafadhali subiri dakika chache kabla ya kuendelea.',
            'location_denied' => 'Sikuweza kupata eneo lako - bado unaweza kuniambia eneo au mahali maarufu.',
            'which_one' => 'Ungependa ipi? Gusa chaguo hapa chini, au niambie aina ya nyumba.',
            'declined' => 'Sawa - ungependa kubadilisha nini? Unaweza kujaribu eneo tofauti, aina ya nyumba au bajeti.',
            'repeat' => 'Bado sijapata nyumba inayolingana na hiyo. Unaweza kujaribu eneo tofauti, aina ya nyumba au bajeti? Pia unaweza kuangalia nyumba zote kwenye ukurasa wa Rent Long-Term.',
            'handoff_intro' => 'Huenda sikuwa msaada bora kwa hili - timu yetu inaweza kukusaidia moja kwa moja:',
            'handoff_requested' => 'Sawa kabisa - unaweza kuwasiliana na timu yetu moja kwa moja hapa:',
            'handoff_unavailable' => 'Siwezi kukuunganisha na timu kutoka hapa kwa sasa, lakini unaweza kutumia ukurasa wa Mawasiliano, au kuendelea kutafuta nami.',
            'link_whatsapp' => 'Tuma WhatsApp',
            'link_call' => 'Tupigie simu',
            'link_email' => 'Tutumie barua pepe',
            'handoff_message' => 'Habari, nahitaji msaada wa kutafuta nyumba kwenye :app.',
            'handoff_looking_for' => ' Nilikuwa natafuta: :summary.',
            'chip_other_areas' => 'Angalia maeneo mengine',
            'chip_show_price' => 'Onyesha nyumba za KES :price',
        ],
    ];

    public static function t(string $key, string $lang = 'en', array $params = []): string
    {
        $text = self::STRINGS[$lang][$key] ?? self::STRINGS['en'][$key] ?? $key;

        foreach ($params as $name => $value) {
            $text = str_replace(':'.$name, (string) $value, $text);
        }

        return $text;
    }

    /** "1 Bedroom in Kasarani under KES 10,000" - plain, no leading/trailing words, used in prefilled contact messages. */
    public static function summary(array $filters, string $lang = 'en'): string
    {
        $in = $lang === 'sw' ? 'huko' : 'in';
        $near = $lang === 'sw' ? 'karibu na' : 'near';
        $under = $lang === 'sw' ? 'chini ya' : 'under';

        $parts = [];

        if (filled($filters['house_type'] ?? null)) {
            $parts[] = $filters['house_type'];
        }

        if (filled($filters['property_name'] ?? null)) {
            $parts[] = ($lang === 'sw' ? 'katika ' : 'at ').$filters['property_name'];
        } elseif (filled($filters['area'] ?? null)) {
            $parts[] = "{$in} {$filters['area']}";
        } elseif (filled($filters['landmark'] ?? null)) {
            $parts[] = "{$near} {$filters['landmark']}";
        }

        if (filled($filters['max_rent'] ?? null)) {
            $parts[] = "{$under} KES ".number_format((int) $filters['max_rent']);
        }

        return implode(' ', $parts);
    }

    /** Swahili counterpart of HouseSearchAiService::describeFilters() - a leading-space phrase to append to a sentence. */
    public static function criteriaSw(array $filters): string
    {
        $type = $filters['house_type'] ?? null;
        $place = match (true) {
            filled($filters['property_name'] ?? null) => "katika {$filters['property_name']}",
            filled($filters['area'] ?? null) => "huko {$filters['area']}",
            filled($filters['landmark'] ?? null) => "karibu na {$filters['landmark']}",
            (bool) ($filters['near_me'] ?? false) => 'karibu nawe',
            default => null,
        };

        $budget = filled($filters['max_rent'] ?? null) ? ' chini ya KES '.number_format((int) $filters['max_rent']) : '';

        return match (true) {
            $type && $place => " ya {$type} {$place}{$budget}",
            (bool) $type => " ya {$type}{$budget}",
            (bool) $place => " {$place}{$budget}",
            default => $budget,
        };
    }

    /** Swahili versions of HouseSearchAiService::fallbackReply(), one per search branch. */
    public static function fallbackSw(array $facts): string
    {
        $criteria = self::criteriaSw($facts['filters'] ?? []);

        return match ($facts['branch'] ?? null) {
            'results' => "Nimepata nyumba {$facts['count']}{$criteria} - angalia hapa chini.",
            'narrow' => "Zipo nyumba {$facts['count']}{$criteria} - hizi hapa chache. Ungependa kupunguza kwa bajeti au eneo?",
            'zero_results' => "Hakuna nyumba kamili{$criteria} kwa sasa. Nikague maeneo mengine?",
            'alternatives_shown' => 'Hakuna katika eneo hilo, lakini hizi hapa zilizopo karibu.',
            'none' => self::noneSw($facts, $criteria),
            'property_not_found' => "Sikupata jengo linaloitwa \"{$facts['requested_property_name']}\". Ungependa nitafute kwa eneo au bajeti?",
            default => 'Unatafuta nyumba ya aina gani - bedsitter, chumba kimoja, vyumba viwili...? Niambie pia eneo na bajeti, mfano "chumba kimoja Kasarani chini ya 20k".',
        };
    }

    protected static function noneSw(array $facts, string $criteria): string
    {
        if (filled($facts['cheapest_available_for_type'] ?? null)) {
            $price = number_format($facts['cheapest_available_for_type']);

            return "Sijapata nyumba inayolingana{$criteria}. Ya bei nafuu zaidi inayopatikana sasa ni takriban KES {$price} - ungependa niangalie hiyo?";
        }

        if (filled($facts['available_house_types'] ?? null)) {
            $types = implode(', ', $facts['available_house_types']);

            return "Sijapata nyumba inayolingana{$criteria}. Zilizopo sasa: {$types}. Ungependa kujaribu mojawapo?";
        }

        return "Sijapata nyumba inayolingana{$criteria}. Jaribu kuongeza bajeti au kubadilisha aina ya nyumba.";
    }
}
