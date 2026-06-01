<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendForumPostCommandStaffNotificationsJob;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\ForumPostCommandStaffNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see SendForumPostCommandStaffNotificationsJob
 */
class SendForumPostCommandStaffNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

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

        $admin = Trooper::factory()->asAdministrator()->create();
        $retired_admin = Trooper::factory()->asAdministrator()->asRetired()->create();

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

        $poster = Trooper::factory()->asAdministrator()->create();
        $other_admin = Trooper::factory()->asAdministrator()->create();

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

        $admin_and_moderator = Trooper::factory()->asAdministrator()->create();
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

    public function test_handle_respects_notification_preference_opt_out(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $poster = Trooper::factory()->asMember()->create();

        $opted_out_admin = Trooper::factory()->asAdministrator()->create([
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
