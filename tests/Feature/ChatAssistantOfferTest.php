<?php

namespace Tests\Feature;

use App\Livewire\ChatAssistant;
use App\Models\ChatTurn;
use App\Models\Setting;
use App\Services\HouseMatchService;
use App\Services\HouseSearchAiService;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Replays the "1 bedroom under 10k" -> "yes please" -> "yes please" loop: the
 * assistant offered KES 14,000, then ignored the acceptance and repeated itself.
 *
 * The panel providers read app_settings while the app boots, and the full
 * migration set doesn't run on sqlite, so this needs a database that already has
 * that one table, e.g. a throwaway sqlite file:
 *   DB_CONNECTION=sqlite CACHE_STORE=array SESSION_DRIVER=array DB_DATABASE=<file> php artisan test --filter=ChatAssistantOfferTest
 * The assistant services themselves are mocked, so nothing else is read or written.
 */
class ChatAssistantOfferTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        cache()->flush();
        ChatTurn::query()->delete();
        Setting::query()->delete();

        // Real fallback extraction/replies, but no OpenRouter call and a configured assistant.
        $ai = Mockery::mock(HouseSearchAiService::class, [app(\App\Services\OpenRouterCatalogService::class)])->makePartial();
        $ai->shouldReceive('isConfigured')->andReturn(true);
        $ai->shouldReceive('extractFilters')->andReturnUsing(fn ($history, $current) => $current ?: null);
        $ai->shouldReceive('extractFiltersFallback')->andReturnUsing(function (string $text) {
            $f = [];
            if (preg_match('/1 bedroom/i', $text)) {
                $f['house_type'] = '1 Bedroom';
            }
            if (preg_match('/(\d+)\s*k/i', $text, $m)) {
                $f['max_rent'] = (int) $m[1] * 1000;
            }

            return $f ?: null;
        });
        $ai->shouldReceive('composeReply')->andReturn('Here are some 1 Bedroom places.');
        $this->app->instance(HouseSearchAiService::class, $ai);

        // 1-beds exist from KES 14,000 only.
        $matcher = Mockery::mock(HouseMatchService::class, [app(\App\Services\LandmarkGeocoder::class)])->makePartial();
        $matcher->shouldReceive('search')->andReturnUsing(function (array $filters) {
            $max = $filters['max_rent'] ?? PHP_INT_MAX;

            if ($max >= 14000) {
                return ['results' => collect([new \App\Models\House]), 'branch' => 'results', 'facts' => ['branch' => 'results', 'count' => 1, 'filters' => $filters]];
            }

            return ['results' => collect(), 'branch' => 'none', 'facts' => [
                'branch' => 'none',
                'filters' => $filters,
                'cheapest_available_for_type' => 14000,
                'available_house_types' => ['1 Bedroom', 'Studio'],
            ]];
        });
        $matcher->shouldReceive('toCards')->andReturn([['id' => 1, 'title' => 'Sunny 1 Bedroom', 'type' => '1 Bedroom', 'area' => 'Rongai', 'price' => 14000, 'price_unit' => 'mo', 'image' => null, 'url' => '#']]);
        $this->app->instance(HouseMatchService::class, $matcher);
    }

    protected function lastMessage($component): array
    {
        $messages = $component->get('messages');

        return end($messages);
    }

    protected function say($component, string $text)
    {
        return $component->set('input', $text)->call('send')->call('reply');
    }

    public function test_yes_to_the_budget_offer_shows_the_cheaper_listing_instead_of_repeating(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, 'can i get a house under 10 k, a 1 bedroom?');

        $first = $this->lastMessage($chat);
        $this->assertStringContainsString('under KES 10,000', $first['text']);
        $this->assertStringContainsString('KES 14,000', $first['text']);
        $this->assertSame('Show KES 14,000 homes', $first['chips'][0]['label']);

        $this->say($chat, 'yes please');

        $second = $this->lastMessage($chat);
        $this->assertNotSame($first['text'], $second['text']);
        $this->assertNotEmpty($second['cards']);
        $this->assertSame(14000, $chat->get('filters')['max_rent']);
    }

    public function test_tapping_the_chip_does_the_same(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, '1 bedroom under 10k');

        $chat->call('pickChip', 0)->call('reply');

        $this->assertNotEmpty($this->lastMessage($chat)['cards']);
        $this->assertSame(14000, $chat->get('filters')['max_rent']);
    }

    public function test_no_declines_the_offer_without_searching_again(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, '1 bedroom under 10k');
        $this->say($chat, 'no thanks');

        $last = $this->lastMessage($chat);
        $this->assertStringContainsString('what would you like to change', $last['text']);
        $this->assertSame(10000, $chat->get('filters')['max_rent']);
        $this->assertNull($chat->get('pendingOffer'));
    }

    public function test_a_repeated_dead_end_changes_tack(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, '1 bedroom under 10k');
        $this->say($chat, '1 bedroom under 10k');

        $this->assertStringContainsString("still not finding a match", $this->lastMessage($chat)['text']);
    }

    public function test_a_swahili_conversation_gets_swahili_replies_and_ndio_accepts_the_offer(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, 'nataka 1 bedroom chini ya 10k');

        $first = $this->lastMessage($chat);
        $this->assertStringContainsString('Sijapata nyumba inayolingana', $first['text']);
        $this->assertStringContainsString('chini ya KES 10,000', $first['text']);
        $this->assertSame('Onyesha nyumba za KES 14,000', $first['chips'][0]['label']);

        $this->say($chat, 'ndio');

        $this->assertNotEmpty($this->lastMessage($chat)['cards']);
        $this->assertSame(14000, $chat->get('filters')['max_rent']);
        $this->assertSame('sw', $chat->get('meta')['lang']);
    }

    public function test_turns_are_logged_with_contact_details_masked_and_offer_outcomes_recorded(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, 'my number is 0712 345 678, 1 bedroom under 10k');
        $this->say($chat, 'yes please');

        $turns = ChatTurn::orderBy('id')->get();

        $this->assertCount(2, $turns);
        $this->assertStringContainsString('[phone]', $turns[0]->user_text);
        $this->assertStringNotContainsString('345', $turns[0]->user_text);
        $this->assertSame('none', $turns[0]->branch);
        $this->assertSame('raise_budget', $turns[0]->offer_type);
        $this->assertSame('accepted', $turns[0]->offer_result);
        $this->assertSame('results', $turns[1]->branch);
        $this->assertSame(1, $turns->pluck('chat_id')->unique()->count());
    }

    public function test_asking_for_a_person_shows_the_contact_links_from_platform_settings(): void
    {
        $settings = Setting::forLandlord(null);
        $settings->payload = [
            'support_whatsapp' => '0712 345 678',
            'support_phone' => '+254 722 000 111',
            'platform_support_email' => 'help@renty.top',
        ];
        $settings->save();

        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, 'can I speak to someone please');

        $message = $this->lastMessage($chat);
        $urls = collect($message['links'])->pluck('url', 'kind');

        $this->assertStringStartsWith('https://wa.me/254712345678?text=', $urls['whatsapp']);
        $this->assertSame('tel:+254722000111', $urls['call']);
        $this->assertStringStartsWith('mailto:help@renty.top?subject=', $urls['email']);
        $this->assertSame('Chat on WhatsApp', collect($message['links'])->firstWhere('kind', 'whatsapp')['label']);

        // Same request in Swahili gets Swahili wording.
        $this->say($chat, 'nataka kuongea na mtu');
        $this->assertSame('Tuma WhatsApp', collect($this->lastMessage($chat)['links'])->firstWhere('kind', 'whatsapp')['label']);
    }

    public function test_the_team_is_offered_after_repeated_confusion_but_only_if_contacts_are_set(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, 'hello there');
        $this->say($chat, 'hmm okay');
        $this->assertSame([], $this->lastMessage($chat)['links'], 'no contacts configured, so no links');

        $settings = Setting::forLandlord(null);
        $settings->payload = ['support_whatsapp' => '0712345678'];
        $settings->save();

        // A brand-new visitor, so the confusion count starts from zero.
        $this->flushSession();
        $chat = Livewire::test(ChatAssistant::class);
        $this->say($chat, 'hello again');
        $this->assertSame([], $this->lastMessage($chat)['links'], 'first confusion only asks again');

        $this->say($chat, 'still unclear');
        $this->assertNotEmpty($this->lastMessage($chat)['links']);
        $this->assertTrue(ChatTurn::orderByDesc('id')->first()->handoff_shown);
    }

    public function test_without_any_contact_set_a_request_for_a_person_says_so_honestly(): void
    {
        $chat = Livewire::test(ChatAssistant::class)->call('toggle');
        $this->say($chat, 'I want to talk to a human');

        $message = $this->lastMessage($chat);
        $this->assertSame([], $message['links']);
        $this->assertStringContainsString("can't connect you", $message['text']);
    }
}
