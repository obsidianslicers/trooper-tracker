<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Jobs\SendForumPostCommandStaffNotificationsJob;
use TroopTracker\Plugins\Xenforo\Mail\ForumPostCommandStaffMail;
use TroopTracker\Plugins\Xenforo\Notifications\ForumPostCommandStaffNotification;

/**
 * @see SendForumPostCommandStaffNotificationsJob
 */
class SendForumPostCommandStaffNotificationsJobTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_handle_sends_notification_to_org_moderators(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $poster = Trooper::factory()->asMember()->create();

        $moderator = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($organization)
            ->asModerator()
            ->create();

        $non_moderator = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()
            ->forTrooper($non_moderator)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $subject = new SendForumPostCommandStaffNotificationsJob($event, $poster);
        $subject->handle();

        Notification::assertSentTo($moderator, ForumPostCommandStaffNotification::class);
        Notification::assertNotSentTo($non_moderator, ForumPostCommandStaffNotification::class);
    }

    public function test_handle_sends_notification_to_active_admins(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $poster = Trooper::factory()->asMember()->create();

        $admin = Trooper::factory()->asSystemOwner()->create();
        $retired_admin = Trooper::factory()->asSystemOwner()->asRetired()->create();

        $subject = new SendForumPostCommandStaffNotificationsJob($event, $poster);
        $subject->handle();

        Notification::assertSentTo($admin, ForumPostCommandStaffNotification::class);
        Notification::assertNotSentTo($retired_admin, ForumPostCommandStaffNotification::class);
    }

    public function test_handle_excludes_poster_from_notifications(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();

        $poster = Trooper::factory()->asSystemOwner()->create();
        $other_admin = Trooper::factory()->asSystemOwner()->create();

        $subject = new SendForumPostCommandStaffNotificationsJob($event, $poster);
        $subject->handle();

        Notification::assertNotSentTo($poster, ForumPostCommandStaffNotification::class);
        Notification::assertSentTo($other_admin, ForumPostCommandStaffNotification::class);
    }

    public function test_handle_sends_only_one_notification_to_admin_who_is_also_org_moderator(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $poster = Trooper::factory()->asMember()->create();

        $admin_and_moderator = Trooper::factory()->asSystemOwner()->create();
        TrooperAssignment::factory()
            ->forTrooper($admin_and_moderator)
            ->forOrganization($organization)
            ->asModerator()
            ->create();

        $subject = new SendForumPostCommandStaffNotificationsJob($event, $poster);
        $subject->handle();

        $sent_count = Notification::sent($admin_and_moderator, ForumPostCommandStaffNotification::class)->count();
        $this->assertSame(1, $sent_count);
    }

    public function test_handle_notifies_the_picked_coordinator_worded_for_them(): void
    {
        Notification::fake();

        $creator = Trooper::factory()->asEventCoordinator()->create();
        $coordinator = Trooper::factory()->asMember()->create();
        $event = Event::factory()->withCreatedByTrooper($creator)->create([Event::COORDINATOR_TROOPER_ID => $coordinator->id]);
        $poster = Trooper::factory()->asMember()->create();

        (new SendForumPostCommandStaffNotificationsJob($event, $poster))->handle();

        Notification::assertSentTo($coordinator, ForumPostCommandStaffNotification::class,
            fn (ForumPostCommandStaffNotification $notification) => str_contains(
                $notification->toArray($coordinator)['body'], "You're getting this as the event's coordinator."));
        Notification::assertNotSentTo($creator, ForumPostCommandStaffNotification::class);
    }

    public function test_handle_notifies_the_creator_when_nobody_is_picked(): void
    {
        Notification::fake();

        $creator = Trooper::factory()->asEventCoordinator()->create();
        $event = Event::factory()->withCreatedByTrooper($creator)->create();
        $poster = Trooper::factory()->asMember()->create();

        (new SendForumPostCommandStaffNotificationsJob($event, $poster))->handle();

        Notification::assertSentTo($creator, ForumPostCommandStaffNotification::class);
    }

    public function test_handle_skips_the_coordinator_who_posted_or_is_not_active(): void
    {
        Notification::fake();

        $coordinator = Trooper::factory()->asMember()->create();
        $event = Event::factory()->create([Event::COORDINATOR_TROOPER_ID => $coordinator->id]);

        (new SendForumPostCommandStaffNotificationsJob($event, $coordinator))->handle();

        Notification::assertNothingSentTo($coordinator);

        $retired = Trooper::factory()->asMember()->asRetired()->create();
        $event->update([Event::COORDINATOR_TROOPER_ID => $retired->id]);

        (new SendForumPostCommandStaffNotificationsJob($event->fresh(), Trooper::factory()->asMember()->create()))->handle();

        Notification::assertNothingSentTo($retired);
    }

    public function test_handle_sends_a_coordinator_on_command_staff_only_the_command_staff_alert(): void
    {
        Notification::fake();

        $admin = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->withCreatedByTrooper($admin)->create();
        $poster = Trooper::factory()->asMember()->create();

        (new SendForumPostCommandStaffNotificationsJob($event, $poster))->handle();

        $sent = Notification::sent($admin, ForumPostCommandStaffNotification::class);
        $this->assertCount(1, $sent);
        $this->assertStringNotContainsString('coordinator', $sent->first()->toArray($admin)['body']);
    }

    public function test_the_email_greets_the_coordinator(): void
    {
        $event = Event::factory()->create();
        $poster = Trooper::factory()->asMember()->create();

        $this->assertStringContainsString('Esteemed Event Coordinator',
            (new ForumPostCommandStaffMail($event, $poster, true))->render());
        $this->assertStringContainsString('Esteemed Command Staff',
            (new ForumPostCommandStaffMail($event, $poster))->render());
    }

    public function test_handle_respects_notification_preference_opt_out(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $poster = Trooper::factory()->asMember()->create();

        $opted_out_admin = Trooper::factory()->asSystemOwner()->create([
            Trooper::NOTIFICATION_PREFERENCES => [
                'forum_post_command_staff' => [
                    'database' => false,
                    'fcm' => false,
                    'mail' => false,
                ],
            ],
        ]);

        $subject = new SendForumPostCommandStaffNotificationsJob($event, $poster);
        $subject->handle();

        Notification::assertNothingSentTo($opted_out_admin);
    }
}
