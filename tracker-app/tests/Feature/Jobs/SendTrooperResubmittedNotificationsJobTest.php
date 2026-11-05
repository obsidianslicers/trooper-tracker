<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\MembershipRole;
use App\Jobs\SendTrooperResubmittedNotificationsJob;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\TrooperResubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendTrooperResubmittedNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_notifies_all_admins(): void
    {
        Notification::fake();

        $resubmitted_trooper = Trooper::factory()->asDenied()->create();

        $admin = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();

        $messages = $this->fakeMessages([
            GetTroopersByRole::class => fn (array $params): mixed => match (true)
            {
                $params['membership_role'] === MembershipRole::ADMINISTRATOR => collect([$admin]),
                $params['membership_role'] === MembershipRole::MODERATOR => collect([]),
            },
        ]);

        $subject = new SendTrooperResubmittedNotificationsJob($resubmitted_trooper);
        $subject->handle();

        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::ADMINISTRATOR
        );
        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::MODERATOR
        );

        Notification::assertSentTo($admin, TrooperResubmittedNotification::class);
    }

    public function test_handle_notifies_moderators_with_authority_over_resubmitted_trooper(): void
    {
        Notification::fake();

        $resubmitted_trooper = Trooper::factory()->asDenied()->create();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $descendant = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();

        TrooperAssignment::factory()
            ->forTrooper($resubmitted_trooper)
            ->forOrganization($descendant)
            ->asMember()
            ->create();

        $moderator_in_tree = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        TrooperAssignment::factory()
            ->forTrooper($moderator_in_tree)
            ->forOrganization($root)
            ->asModerator()
            ->create();

        $moderator_outside_tree = Trooper::factory()->asModerator()->withEmail('other@example.com')->create();

        $messages = $this->fakeMessages([
            GetTroopersByRole::class => fn (array $params): mixed => match (true)
            {
                $params['membership_role'] === MembershipRole::ADMINISTRATOR => collect([]),
                $params['membership_role'] === MembershipRole::MODERATOR => collect([$moderator_in_tree, $moderator_outside_tree]),
            },
        ]);

        $subject = new SendTrooperResubmittedNotificationsJob($resubmitted_trooper);
        $subject->handle();

        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::ADMINISTRATOR
        );
        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::MODERATOR
        );

        Notification::assertSentTo($moderator_in_tree, TrooperResubmittedNotification::class);
        Notification::assertNotSentTo($moderator_outside_tree, TrooperResubmittedNotification::class);
    }

    public function test_handle_does_not_notify_moderators_without_authority(): void
    {
        Notification::fake();

        $resubmitted_trooper = Trooper::factory()->asDenied()->create();
        $moderator = Trooper::factory()->asModerator()->create();

        $messages = $this->fakeMessages([
            GetTroopersByRole::class => fn (array $params): mixed => match (true)
            {
                $params['membership_role'] === MembershipRole::ADMINISTRATOR => collect([]),
                $params['membership_role'] === MembershipRole::MODERATOR => collect([$moderator]),
            },
        ]);

        $subject = new SendTrooperResubmittedNotificationsJob($resubmitted_trooper);
        $subject->handle();

        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::ADMINISTRATOR
        );
        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::MODERATOR
        );

        Notification::assertNotSentTo($moderator, TrooperResubmittedNotification::class);
    }
}
