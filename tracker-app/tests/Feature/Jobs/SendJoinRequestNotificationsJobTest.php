<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\JoinRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

/**
 * @see \App\Jobs\SendJoinRequestNotificationsJob
 */
class SendJoinRequestNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_all_admins(): void
    {
        Notification::fake();

        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asMember()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $admin_valid = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();
        $admin_invalid = Trooper::factory()->asAdministrator()->withInvalidEmail()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn(object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([$admin_valid, $admin_invalid]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn(object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([]));

        $subject = new SendJoinRequestNotificationsJob($trooper_request);
        $subject->handle($bus);

        Notification::assertSentTo($admin_valid, JoinRequestSubmittedNotification::class);
        Notification::assertSentTo($admin_invalid, JoinRequestSubmittedNotification::class);
    }

    public function test_handle_notifies_moderators_with_authority_over_request(): void
    {
        Notification::fake();

        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $child = Organization::factory()->asRegion()->withNodePath('100:200:')->withParent($root)->create();

        $trooper = Trooper::factory()->asMember()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($child)
            ->forPrimaryOrganization($root)
            ->create();

        $moderator_in_tree = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        TrooperAssignment::factory()->forTrooper($moderator_in_tree)->forOrganization($root)->asModerator()->create();

        $moderator_outside_tree = Trooper::factory()->asModerator()->withEmail('other-mod@example.com')->create();
        $other_org = Organization::factory()->asOrganization()->withNodePath('999:')->create();
        TrooperAssignment::factory()->forTrooper($moderator_outside_tree)->forOrganization($other_org)->asModerator()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn(object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn(object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([$moderator_in_tree, $moderator_outside_tree]));

        $subject = new SendJoinRequestNotificationsJob($trooper_request);
        $subject->handle($bus);

        Notification::assertSentTo($moderator_in_tree, JoinRequestSubmittedNotification::class);
        Notification::assertNotSentTo($moderator_outside_tree, JoinRequestSubmittedNotification::class);
    }
}
