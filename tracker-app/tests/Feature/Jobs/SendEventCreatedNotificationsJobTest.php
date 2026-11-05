<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendEventCreatedNotificationsJob;
use App\Messages\Events\Commands\SendEventCreatedNotification;
use App\Messages\Events\Queries\GetTroopersForEventCreated;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\Forums\ForumThreadMessageService;
use App\Services\Forums\XenforoService;
use App\Services\Notifications\DiscordNotifier;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendEventCreatedNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_sends_created_notifications_and_marks_timestamp(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->withForumThreadDisabled()->create();
        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventCreated::class => collect([$trooper_one, $trooper_two]),
            SendEventCreatedNotification::class => fn (array $params): mixed => match (true)
            {
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id => null,
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id => null,
            },
        ]);

        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);
        $notifier = new DiscordNotifier;

        $forumThreadMessageService = Mockery::mock(ForumThreadMessageService::class);
        $forumThreadMessageService->shouldNotReceive('buildThreadMessage');

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldNotReceive('resolve_user_id_for_trooper');
        $xenforo->shouldNotReceive('create_thread');

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle($notifier, $forumThreadMessageService, $xenforo);

        $messages->assertCalled(GetTroopersForEventCreated::class);
        $messages->assertCalled(
            SendEventCreatedNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id
        );
        $messages->assertCalled(
            SendEventCreatedNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id
        );

        $this->assertNotNull($event->fresh()->create_notifications_sent_at);
    }

    public function test_handle_rolls_back_marker_when_recipient_query_fails(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->withForumThreadDisabled()->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventCreated::class => fn (array $params): never => throw new Exception('query failed'),
        ]);

        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);
        $notifier = new DiscordNotifier;

        $forumThreadMessageService = Mockery::mock(ForumThreadMessageService::class);
        $xenforo = Mockery::mock(XenforoService::class);

        $subject = new SendEventCreatedNotificationsJob($event);

        $this->expectException(Exception::class);

        try
        {
            $subject->handle($notifier, $forumThreadMessageService, $xenforo);
        }
        finally
        {
            // Rolled back with the failed fan-out, so a retry re-sends to everyone.
            $this->assertNull($event->fresh()->create_notifications_sent_at);
        }
    }

    public function test_handle_skips_trooper_notifications_when_already_marked_sent(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withCreateNotificationsSent()
            ->withForumThreadDisabled()
            ->create();

        $messages = $this->fakeMessages([]);

        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);
        $notifier = new DiscordNotifier;

        $forumThreadMessageService = Mockery::mock(ForumThreadMessageService::class);
        $forumThreadMessageService->shouldNotReceive('buildThreadMessage');

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldNotReceive('resolve_user_id_for_trooper');
        $xenforo->shouldNotReceive('create_thread');

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle($notifier, $forumThreadMessageService, $xenforo);

        $messages->assertNotCalled(GetTroopersForEventCreated::class);
        $messages->assertNotCalled(SendEventCreatedNotification::class);

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

        $messages = $this->fakeMessages([]);

        config([
            'discord.webhooks.default' => null,
            'discord.webhook_url' => null,
            'services.xenforo.base_url' => 'https://forums.example.com',
            'services.xenforo.api_key' => 'abc',
        ]);
        $notifier = new DiscordNotifier;

        $forumThreadMessageService = Mockery::mock(ForumThreadMessageService::class);
        $forumThreadMessageService->shouldReceive('buildThreadMessage')
            ->once()
            ->withArgs(fn (Event $actualEvent): bool => $actualEvent->id === $event->id)
            ->andReturn('[b]Rendered thread message[/b]');

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')
            ->once()
            ->with($creator->id)
            ->andReturn(9876);

        $xenforo->shouldReceive('create_thread')
            ->once()
            ->with(42, $event->name, '[b]Rendered thread message[/b]', 9876)
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
        $subject->handle($notifier, $forumThreadMessageService, $xenforo);

        $messages->assertNotCalled(GetTroopersForEventCreated::class);
        $messages->assertNotCalled(SendEventCreatedNotification::class);

        $event = $event->fresh();

        $this->assertSame(321, $event->thread_id);
        $this->assertSame(654, $event->post_id);
    }
}
