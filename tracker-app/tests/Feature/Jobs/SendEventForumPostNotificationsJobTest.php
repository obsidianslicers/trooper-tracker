<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\OauthProvider;
use App\Jobs\SendEventForumPostNotificationsJob;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Notifications\Events\EventForumPostNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see SendEventForumPostNotificationsJob
 */
class SendEventForumPostNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_event_watchers(): void
    {
        Notification::fake();

        $event = Event::factory()->withForumThreadId(100)->create();
        $watcher = Trooper::factory()->asMember()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($watcher)->create();

        $subject = new SendEventForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertSentTo($watcher, EventForumPostNotification::class);
    }

    public function test_handle_does_not_notify_troopers_not_watching_event(): void
    {
        Notification::fake();

        $event = Event::factory()->withForumThreadId(100)->create();
        $non_watcher = Trooper::factory()->asMember()->create();

        $subject = new SendEventForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertNothingSentTo($non_watcher);
    }

    public function test_handle_excludes_poster_when_they_have_a_linked_xenforo_account(): void
    {
        Notification::fake();

        $event = Event::factory()->withForumThreadId(100)->create();

        $poster = Trooper::factory()->asMember()->create();
        OauthLogin::factory()->forTrooper($poster)->create([
            OauthLogin::PROVIDER    => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '42',
        ]);
        EventWatch::factory()->forEvent($event)->forTrooper($poster)->create();

        $other_watcher = Trooper::factory()->asMember()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($other_watcher)->create();

        $subject = new SendEventForumPostNotificationsJob($event, 999, 'Poster', 42, 'Test reply');
        $subject->handle();

        Notification::assertNotSentTo($poster, EventForumPostNotification::class);
        Notification::assertSentTo($other_watcher, EventForumPostNotification::class);
    }

    public function test_handle_does_not_exclude_watcher_with_different_xenforo_id(): void
    {
        Notification::fake();

        $event = Event::factory()->withForumThreadId(100)->create();

        $trooper = Trooper::factory()->asMember()->create();
        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER    => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '99',
        ]);
        EventWatch::factory()->forEvent($event)->forTrooper($trooper)->create();

        $subject = new SendEventForumPostNotificationsJob($event, 999, 'SomeoneElse', 42, 'Test reply');
        $subject->handle();

        Notification::assertSentTo($trooper, EventForumPostNotification::class);
    }

    public function test_handle_does_not_exclude_poster_when_xenforo_user_id_is_null(): void
    {
        Notification::fake();

        $event = Event::factory()->withForumThreadId(100)->create();
        $watcher = Trooper::factory()->asMember()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($watcher)->create();

        $subject = new SendEventForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertSentTo($watcher, EventForumPostNotification::class);
    }

    public function test_handle_respects_notification_preference_opt_out(): void
    {
        Notification::fake();

        $event = Event::factory()->withForumThreadId(100)->create();

        $opted_out = Trooper::factory()->asMember()->create([
            Trooper::NOTIFICATION_PREFERENCES => [
                'event_forum_post' => [
                    'database' => false,
                    'fcm'      => false,
                    'mail'     => false,
                ],
            ],
        ]);
        EventWatch::factory()->forEvent($event)->forTrooper($opted_out)->create();

        $subject = new SendEventForumPostNotificationsJob($event, 999, 'Poster', null, 'Test reply');
        $subject->handle();

        Notification::assertNothingSentTo($opted_out);
    }
}
