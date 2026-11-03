<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Discord;

use App\Events\EventPosted;
use App\Facades\TroopTracker;
use App\Models\Event;
use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\DiscordNotifier;

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

        $organization = Organization::factory()->create([Organization::DISCORD_MENTION => '<@&777>']);
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
        $this->assertTrue((new TroopTracker)->isDiscordIntegrationConfigured());
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
        $this->assertFalse((new TroopTracker)->isDiscordIntegrationConfigured());
    }

    private function enableDiscord(): void
    {
        $this->plugins->enable('discord');
        $this->plugins->load($this->plugins->findOrFail('discord'));
    }
}
