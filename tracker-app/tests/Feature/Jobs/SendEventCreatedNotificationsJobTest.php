<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventCreatedQuery;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use App\Services\Notifications\DiscordNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendEventCreatedNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_created_notifications_and_marks_timestamp(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->withForumThreadDisabled()->create();
        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn(object $query): bool => $query instanceof GetTroopersForEventCreatedQuery)
            ->andReturn(collect([$trooper_one, $trooper_two]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $command) use ($event, $trooper_one): bool
            {
                return $command instanceof SendEventCreatedNotificationCommand
                    && $command->event->id === $event->id
                    && $command->trooper->id === $trooper_one->id;
            });

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $command) use ($event, $trooper_two): bool
            {
                return $command instanceof SendEventCreatedNotificationCommand
                    && $command->event->id === $event->id
                    && $command->trooper->id === $trooper_two->id;
            });

        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);
        $notifier = new DiscordNotifier;

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldNotReceive('resolve_user_id_for_trooper');
        $xenforo->shouldNotReceive('create_thread');

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle($bus, $notifier, $xenforo);

        $this->assertNotNull($event->fresh()->create_notifications_sent_at);
    }

    public function test_handle_skips_trooper_notifications_when_already_marked_sent(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withCreateNotificationsSent()
            ->withForumThreadDisabled()
            ->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldNotReceive('send');

        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);
        $notifier = new DiscordNotifier;

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldNotReceive('resolve_user_id_for_trooper');
        $xenforo->shouldNotReceive('create_thread');

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle($bus, $notifier, $xenforo);

        $this->assertNotNull($event->fresh()->create_notifications_sent_at);
    }

    public function test_handle_creates_xenforo_thread_when_forum_configuration_exists(): void
    {
        $organization = Organization::factory()->withRelatedForum('42')->create();
        $creator = Trooper::factory()->create();

        $event = Event::factory()
            ->withOrganization($organization)
            ->withCreatedByTrooper($creator)
            ->withCreateNotificationsSent()
            ->withForumThreadEnabled()
            ->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldNotReceive('send');

        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);
        $notifier = new DiscordNotifier;

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')
            ->once()
            ->with($creator->id)
            ->andReturn(9876);

        $xenforo->shouldReceive('create_thread')
            ->once()
            ->with(42, $event->name, Mockery::type('string'), 9876)
            ->andReturn([
                'status' => 201,
                'body' => [
                    'thread' => [
                        'thread_id' => 321,
                        'first_post_id' => 654,
                    ],
                ],
            ]);

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle($bus, $notifier, $xenforo);

        $event = $event->fresh();

        $this->assertSame(321, $event->thread_id);
        $this->assertSame(654, $event->post_id);
    }
}
