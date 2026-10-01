<?php

namespace Tests\Feature;

use App\Filament\Superadmin\Pages\ChatInsights;
use App\Filament\Superadmin\Resources\ChatAliasResource\Pages\ListChatAliases;
use App\Filament\Superadmin\Resources\ChatTurnResource\Pages\ListChatTurns;
use App\Filament\Superadmin\Widgets\ChatInsightsStats;
use App\Filament\Superadmin\Widgets\ChatTurnsChart;
use App\Filament\Superadmin\Widgets\ChatUnmetDemand;
use App\Models\ChatAlias;
use App\Models\ChatTurn;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Smoke test: the superadmin chat dashboard pages render with real rows, and
 * "Teach" turns a failed message into a working alias. Same database
 * requirement as ChatAssistantOfferTest (see its docblock).
 */
class ChatInsightsPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        cache()->flush();
        ChatTurn::query()->delete();
        ChatAlias::query()->delete();

        // Not saved - the pages only need an authenticated superadmin, not a users table.
        $this->actingAs(new User(['name' => 'Root', 'email' => 'root@example.com', 'role' => 'superadmin']));
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));
    }

    protected function seedTurns(): ChatTurn
    {
        ChatTurn::create(['chat_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'user_text' => 'hi', 'reply_text' => 'Hello', 'language' => 'en', 'branch' => 'results', 'filters' => ['house_type' => '1 Bedroom']]);
        ChatTurn::create(['chat_id' => 'aaaaaaaa-0000-0000-0000-000000000001', 'user_text' => 'nipe kasa', 'reply_text' => 'Tell me more', 'language' => 'sw', 'branch' => 'clarify', 'filters' => []]);

        return ChatTurn::create(['chat_id' => 'aaaaaaaa-0000-0000-0000-000000000002', 'user_text' => '1 bedroom under 10k', 'reply_text' => 'Sorry', 'language' => 'en', 'branch' => 'none', 'filters' => ['house_type' => '1 Bedroom', 'max_rent' => 10000]]);
    }

    public function test_insight_widgets_render(): void
    {
        $this->seedTurns();

        Livewire::test(ChatInsightsStats::class)->assertSee('Conversations')->assertSee('Swahili messages');
        Livewire::test(ChatTurnsChart::class)->assertSuccessful();
        Livewire::test(ChatUnmetDemand::class)->assertSee('1 Bedroom')->assertSee('KES 10,000');
        Livewire::test(ChatInsights::class)->assertSuccessful();
    }

    public function test_failure_inbox_lists_dead_ends_only_and_teach_creates_a_working_alias(): void
    {
        $dead = $this->seedTurns();
        $clarify = ChatTurn::where('branch', 'clarify')->first();

        Livewire::test(ListChatTurns::class)
            ->assertCanSeeTableRecords([$dead, $clarify])
            ->assertCanNotSeeTableRecords([ChatTurn::where('branch', 'results')->first()])
            ->callTableAction('teach', $clarify, data: ['type' => 'yes', 'phrase' => 'Nipe', 'language' => 'sw'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('taught', $clarify->fresh()->status);
        $this->assertDatabaseHas('chat_aliases', ['type' => 'yes', 'phrase' => 'nipe', 'is_active' => true]);
        $this->assertTrue(app(\App\Services\ChatAliasService::class)->isYes('nipe'));
    }

    public function test_taught_phrases_page_lists_aliases(): void
    {
        $alias = ChatAlias::create(['type' => 'human', 'phrase' => 'Niite mtu', 'language' => 'sw']);

        Livewire::test(ListChatAliases::class)->assertCanSeeTableRecords([$alias]);
        $this->assertTrue(app(\App\Services\ChatAliasService::class)->asksForHuman('naomba niite mtu sasa'));
    }

    public function test_support_contacts_saved_in_platform_settings_reach_the_chat_links(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        \App\Models\Setting::query()->delete();
        cache()->flush();

        Livewire::test(\App\Livewire\SuperadminApp\PlatformSettings::class)
            ->set('activeTab', 'support')
            ->assertSee('WhatsApp number')
            ->set('data.support_whatsapp', '0700 111 222')
            ->set('data.support_phone', 'not a number!')
            ->call('save')
            ->assertHasErrors(['data.support_phone'])
            ->set('data.support_phone', '+254 700 111 333')
            ->set('data.platform_support_email', 'help@renty.top')
            ->call('save')
            ->assertHasNoErrors();

        cache()->flush();
        $urls = collect(app(\App\Services\SupportContactService::class)->links('en'))->pluck('url', 'kind');

        $this->assertStringStartsWith('https://wa.me/254700111222', $urls['whatsapp']);
        $this->assertSame('tel:+254700111333', $urls['call']);
        $this->assertStringStartsWith('mailto:help@renty.top', $urls['email']);
    }

    public function test_app_shell_insights_page_shows_the_numbers(): void
    {
        $this->seedTurns();

        Livewire::test(\App\Livewire\SuperadminApp\ChatInsights::class)
            ->assertSee('Searches that found homes')
            ->assertSee('Unmet demand')
            ->assertSee('1 Bedroom')
            ->assertSee('around KES 10,000');
    }

    public function test_app_shell_inbox_teaches_and_ignores(): void
    {
        $dead = $this->seedTurns();
        $clarify = ChatTurn::where('branch', 'clarify')->first();
        $fine = ChatTurn::where('branch', 'results')->first();

        $inbox = Livewire::test(\App\Livewire\SuperadminApp\ChatInbox::class)
            ->assertSee('1 bedroom under 10k')
            ->assertSee('nipe kasa')
            ->assertDontSee('>hi<', false);

        // A phrase that must point somewhere can't be saved without a meaning...
        $inbox->call('startTeaching', $clarify->id)
            ->set('teachType', 'area')
            ->set('teachPhrase', 'kasa')
            ->call('saveTeaching')
            ->assertHasErrors(['teachCanonical']);

        // ...but a yes/no word needs none, and takes effect straight away.
        $inbox->set('teachType', 'yes')
            ->set('teachPhrase', 'Kasa')
            ->call('saveTeaching')
            ->assertHasNoErrors();

        $this->assertSame('taught', $clarify->fresh()->status);
        $this->assertDatabaseHas('chat_aliases', ['type' => 'yes', 'phrase' => 'kasa']);

        $inbox->call('ignore', $dead->id);
        $this->assertSame('ignored', $dead->fresh()->status);

        $inbox->set('filter', 'all')->assertSee('hi');
    }

    public function test_app_shell_phrases_page_adds_edits_toggles_and_deletes(): void
    {
        $page = Livewire::test(\App\Livewire\SuperadminApp\ChatPhrases::class)
            ->call('create')
            ->set('type', 'house_type')
            ->set('phrase', 'Self Contained')
            ->set('canonical', 'Not a unit type')
            ->call('save')
            ->assertHasErrors(['canonical'])
            ->set('canonical', 'Studio')
            ->call('save')
            ->assertHasNoErrors();

        $alias = ChatAlias::where('phrase', 'self contained')->firstOrFail();
        $this->assertSame('Studio', $alias->canonical);

        $page->call('toggleActive', $alias->id);
        $this->assertFalse($alias->fresh()->is_active);

        $page->call('edit', $alias->id)->set('canonical', 'Bedsitter')->call('save')->assertHasNoErrors();
        $this->assertSame('Bedsitter', $alias->fresh()->canonical);

        $page->call('create')->set('type', 'house_type')->set('phrase', 'self contained')->set('canonical', 'Studio')
            ->call('save')->assertHasErrors(['phrase']);

        $page->call('delete', $alias->id);
        $this->assertDatabaseMissing('chat_aliases', ['id' => $alias->id]);
    }

    public function test_the_three_pages_are_reachable_in_the_app_and_in_the_superadmin_menu(): void
    {
        $this->get(route('app.superadmin.chat-insights'))->assertOk()->assertSee('Failure inbox');
        $this->get(route('app.superadmin.chat-inbox'))->assertOk()->assertSee('Needs attention');
        $this->get(route('app.superadmin.chat-phrases'))->assertOk()->assertSee('Teach a phrase');

        $labels = collect(\App\Support\AppNavigation::forRole('superadmin'))->pluck('label');
        $this->assertTrue($labels->contains('Chat Insights') && $labels->contains('Chat Inbox') && $labels->contains('Taught Phrases'));
    }
}
