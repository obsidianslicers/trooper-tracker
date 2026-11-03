<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Discord;

use App\Enums\SystemCheckStatus;
use App\Events\EventPosted;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginManager;
use App\Plugins\PluginSystemChecks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\DiscordNotifier;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

class DiscordPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $plugins;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins = app(PluginManager::class);
    }

    public function test_event_posted_is_announced_once_the_plugin_is_enabled(): void
    {
        Http::fake();
        $this->enableDiscord();
        $this->plugins->updateSettings('discord', ['webhook_url' => 'https://discord.test/saved']);

        $organization = Organization::factory()->create();
        DiscordOrganizationMention::store($organization->id, '<@&777>');
        $event = Event::factory()->create([Event::ORGANIZATION_ID => $organization->id]);

        EventPosted::dispatch($event);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://discord.test/saved'
            && str_contains((string) $request->data()['content'], '<@&777>')
            && $request->data()['embeds'][0]['title'] === $event->name);
    }

    public function test_webhook_falls_back_to_the_env_setting(): void
    {
        config(['plugins.discord.webhook_url' => 'https://discord.test/env']);
        $this->enableDiscord();

        $this->assertTrue(app(DiscordNotifier::class)->isConfigured());
    }

    public function test_a_discord_outage_does_not_break_event_posting(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));
        $this->enableDiscord();
        $this->plugins->updateSettings('discord', ['webhook_url' => 'https://discord.test/saved']);

        EventPosted::dispatch(Event::factory()->create());

        $this->addToAssertionCount(1);
    }

    public function test_nothing_is_sent_while_the_plugin_is_disabled(): void
    {
        Http::fake();
        config(['plugins.discord.webhook_url' => 'https://discord.test/env']);

        EventPosted::dispatch(Event::factory()->create());

        Http::assertNothingSent();
    }

    public function test_system_check_reports_the_webhook(): void
    {
        $this->enableDiscord();

        $results = app(PluginSystemChecks::class)->run()->keyBy('label');

        $this->assertSame(SystemCheckStatus::PASS, $results['Discord plugin loaded']->status);
        $this->assertSame(SystemCheckStatus::WARN, $results['Discord webhook']->status);
    }

    public function test_organization_form_shows_and_saves_the_mention(): void
    {
        $this->enableDiscord();
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        DiscordOrganizationMention::store($organization->id, '<@&111>');

        $this->actingAs($admin)
            ->get(route('admin.organizations.update', ['organization' => $organization]))
            ->assertOk()
            ->assertSee('Discord Mention:')
            ->assertSee('&lt;@&amp;111&gt;', false);

        $this->actingAs($admin)->post(route('admin.organizations.update', ['organization' => $organization]), [
            Organization::NAME => $organization->name,
            'discord_mention' => '<@&222>',
        ])->assertRedirect(route('admin.organizations.list'));

        $this->assertSame('<@&222>', DiscordOrganizationMention::forOrganization($organization->id));
    }

    public function test_organization_form_validates_the_mention_length(): void
    {
        $this->enableDiscord();
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)->post(route('admin.organizations.update', ['organization' => $organization]), [
            Organization::NAME => $organization->name,
            'discord_mention' => str_repeat('a', 129),
        ])->assertSessionHasErrors('discord_mention');
    }

    public function test_organization_form_has_no_discord_field_while_disabled(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.organizations.update', ['organization' => $organization]))
            ->assertOk()
            ->assertDontSee('Discord Mention:');
    }

    private function enableDiscord(): void
    {
        $this->plugins->enable('discord');
        $this->plugins->load($this->plugins->findOrFail('discord'));
    }
}
