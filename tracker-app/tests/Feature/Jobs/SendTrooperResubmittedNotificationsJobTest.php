<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Jobs\SendTrooperResubmittedNotificationsJob;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\TrooperResubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class SendTrooperResubmittedNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_all_admins(): void
    {
        Notification::fake();

        $resubmitted_trooper = Trooper::factory()->asDenied()->create();

        $admin = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([$admin]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([]));

        $subject = new SendTrooperResubmittedNotificationsJob($resubmitted_trooper);
        $subject->handle($bus);

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

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([$moderator_in_tree, $moderator_outside_tree]));

        $subject = new SendTrooperResubmittedNotificationsJob($resubmitted_trooper);
        $subject->handle($bus);

        Notification::assertSentTo($moderator_in_tree, TrooperResubmittedNotification::class);
        Notification::assertNotSentTo($moderator_outside_tree, TrooperResubmittedNotification::class);
    }

    public function test_handle_does_not_notify_moderators_without_authority(): void
    {
        Notification::fake();

        $resubmitted_trooper = Trooper::factory()->asDenied()->create();
        $moderator = Trooper::factory()->asModerator()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([$moderator]));

        $subject = new SendTrooperResubmittedNotificationsJob($resubmitted_trooper);
        $subject->handle($bus);

        Notification::assertNotSentTo($moderator, TrooperResubmittedNotification::class);
    }
}
