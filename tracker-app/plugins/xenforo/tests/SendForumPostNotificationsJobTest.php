<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use App\Models\EventWatch;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;
use TroopTracker\Plugins\Xenforo\Jobs\SendForumPostNotificationsJob;
use TroopTracker\Plugins\Xenforo\Notifications\EventForumPostNotification;

/**
 * @see SendForumPostNotificationsJob
 */
class SendForumPostNotificationsJobTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_handle_notifies_event_watchers(): void
    {
        Notification::fake();

        $event = $this->linkThread(Event::factory()->create(), 100);
        $watcher = Trooper::factory()->asMember()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($watcher)->create();

        $subject = new SendForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertSentTo($watcher, EventForumPostNotification::class);
    }

    public function test_handle_does_not_notify_troopers_not_watching_event(): void
    {
        Notification::fake();

        $event = $this->linkThread(Event::factory()->create(), 100);
        $non_watcher = Trooper::factory()->asMember()->create();

        $subject = new SendForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertNothingSentTo($non_watcher);
    }

    public function test_handle_excludes_poster_when_they_have_a_linked_xenforo_account(): void
    {
        Notification::fake();

        $event = $this->linkThread(Event::factory()->create(), 100);

        $poster = Trooper::factory()->asMember()->create();
        OauthLogin::factory()->forTrooper($poster)->create([
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '42',
        ]);
        EventWatch::factory()->forEvent($event)->forTrooper($poster)->create();

        $other_watcher = Trooper::factory()->asMember()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($other_watcher)->create();

        $subject = new SendForumPostNotificationsJob($event, 999, 'Poster', 42, 'Test reply');
        $subject->handle();

        Notification::assertNotSentTo($poster, EventForumPostNotification::class);
        Notification::assertSentTo($other_watcher, EventForumPostNotification::class);
    }

    public function test_handle_does_not_exclude_watcher_with_different_xenforo_id(): void
    {
        Notification::fake();

        $event = $this->linkThread(Event::factory()->create(), 100);

        $trooper = Trooper::factory()->asMember()->create();
        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '99',
        ]);
        EventWatch::factory()->forEvent($event)->forTrooper($trooper)->create();

        $subject = new SendForumPostNotificationsJob($event, 999, 'SomeoneElse', 42, 'Test reply');
        $subject->handle();

        Notification::assertSentTo($trooper, EventForumPostNotification::class);
    }

    public function test_handle_does_not_exclude_poster_when_xenforo_user_id_is_null(): void
    {
        Notification::fake();

        $event = $this->linkThread(Event::factory()->create(), 100);
        $watcher = Trooper::factory()->asMember()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($watcher)->create();

        $subject = new SendForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertSentTo($watcher, EventForumPostNotification::class);
    }

    public function test_handle_respects_notification_preference_opt_out(): void
    {
        Notification::fake();

        $event = $this->linkThread(Event::factory()->create(), 100);

        $opted_out = Trooper::factory()->asMember()->create([
            Trooper::NOTIFICATION_PREFERENCES => [
                'event_forum_post' => [
                    'database' => false,
                    'fcm' => false,
                    'mail' => false,
                ],
            ],
        ]);
        EventWatch::factory()->forEvent($event)->forTrooper($opted_out)->create();

        $subject = new SendForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertNothingSentTo($opted_out);
    }
}
