<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\TrooperRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class SendTrooperRegisteredNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_all_admins(): void
    {
        Notification::fake();

        $registered_trooper = Trooper::factory()->asMember()->create();

        $admin_valid = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();
        $admin_invalid = Trooper::factory()->asAdministrator()->withInvalidEmail()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([$admin_valid, $admin_invalid]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([]));

        $subject = new SendTrooperRegisteredNotificationsJob($registered_trooper);
        $subject->handle($bus);

        Notification::assertSentTo($admin_valid, TrooperRegisteredNotification::class);
        Notification::assertSentTo($admin_invalid, TrooperRegisteredNotification::class);
    }

    public function test_handle_notifies_moderators_with_authority_over_registered_trooper(): void
    {
        Notification::fake();

        $registered_trooper = Trooper::factory()->asMember()->create();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $descendant = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();

        TrooperAssignment::factory()
            ->forTrooper($registered_trooper)
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

        $subject = new SendTrooperRegisteredNotificationsJob($registered_trooper);
        $subject->handle($bus);

        Notification::assertSentTo($moderator_in_tree, TrooperRegisteredNotification::class);
        Notification::assertNotSentTo($moderator_outside_tree, TrooperRegisteredNotification::class);
    }
}
