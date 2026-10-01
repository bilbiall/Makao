<?php

namespace Tests\Unit;

use App\Services\ChatAliasService;
use App\Support\ChatCopy;
use App\Support\ChatLanguage;
use App\Support\ChatMasker;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic checks for the Swahili/Sheng layer and the privacy masker - none
 * of these touch the framework or database.
 */
class ChatLocalizationTest extends TestCase
{
    public function test_it_tells_swahili_from_english_and_stays_neutral_on_short_replies(): void
    {
        $this->assertSame('sw', ChatLanguage::detect('nataka bedsitter Kasarani chini ya 10k'));
        $this->assertSame('sw', ChatLanguage::detect('ndio'));
        $this->assertSame('sw', ChatLanguage::detect('Habari, kuna nyumba ya vyumba viwili?'));
        $this->assertSame('en', ChatLanguage::detect('can i get a house under 10 k, a 1 bedroom?'));
        $this->assertSame('en', ChatLanguage::detect('yes please'));
        $this->assertNull(ChatLanguage::detect('ok'));
        $this->assertNull(ChatLanguage::detect('1br 15000'));
    }

    public function test_masker_hides_contact_details_but_keeps_budgets(): void
    {
        $this->assertSame('call me on [phone]', ChatMasker::mask('call me on 0712 345 678'));
        $this->assertSame('[phone]', ChatMasker::mask('+254712345678'));
        $this->assertSame('mail [email] please', ChatMasker::mask('mail jane.doe@gmail.com please'));
        $this->assertSame('my id is [number]', ChatMasker::mask('my id is 12345678'));
        $this->assertSame('1 bedroom under KES 14,000 or 10k', ChatMasker::mask('1 bedroom under KES 14,000 or 10k'));
        $this->assertSame('budget 15000', ChatMasker::mask('budget 15000'));
    }

    public function test_built_in_swahili_words_are_understood_without_a_database(): void
    {
        $aliases = new ChatAliasService;

        $this->assertTrue($aliases->isYes('ndio'));
        $this->assertTrue($aliases->isYes('Sawa, asante'));
        $this->assertFalse($aliases->isYes('sawa lakini nataka kitu kingine kabisa leo'));
        $this->assertTrue($aliases->isNo('hapana'));
        $this->assertFalse($aliases->isNo('hapo ni sawa'));

        $this->assertTrue($aliases->asksForHuman('nataka kuongea na mtu'));
        $this->assertTrue($aliases->asksForHuman('Can I talk to a real person?'));
        $this->assertFalse($aliases->asksForHuman('1 bedroom in Kasarani under 20k'));

        $this->assertSame('2 Bedroom', $aliases->houseTypeFor('nataka vyumba viwili Roysambu'));
        $this->assertSame('1 Bedroom', $aliases->houseTypeFor('chumba kimoja cha kulala'));
        $this->assertSame('Single Room', $aliases->houseTypeFor('chumba kimoja tu'));
        $this->assertSame('Bedsitter', $aliases->houseTypeFor('bedsitta Githurai'));
        $this->assertNull($aliases->houseTypeFor('anything with a garden'));
    }

    public function test_swahili_fallback_replies_never_mention_anything_outside_the_facts(): void
    {
        $facts = [
            'branch' => 'none',
            'filters' => ['house_type' => '1 Bedroom', 'max_rent' => 10000],
            'cheapest_available_for_type' => 14000,
        ];

        $this->assertSame(
            'Sijapata nyumba inayolingana ya 1 Bedroom chini ya KES 10,000. Ya bei nafuu zaidi inayopatikana sasa ni takriban KES 14,000 - ungependa niangalie hiyo?',
            ChatCopy::fallbackSw($facts),
        );

        $this->assertStringContainsString('chumba kimoja', ChatCopy::fallbackSw(['branch' => 'clarify']));
    }
}
