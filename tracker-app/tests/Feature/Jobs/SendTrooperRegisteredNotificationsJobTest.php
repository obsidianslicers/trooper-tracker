<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Admin\Troopers\TrooperAwaitingApproval;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class SendTrooperRegisteredNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_queues_notifications_for_valid_admins_and_allowed_moderators(): void
    {
        Mail::fake();

        $registered_trooper = Trooper::factory()->asMember()->create();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $descendant = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();

        TrooperAssignment::factory()
            ->forTrooper($registered_trooper)
            ->forOrganization($descendant)
            ->asMember()
            ->create();

        $admin_valid = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();
        $admin_invalid = Trooper::factory()->asAdministrator()->withInvalidEmail()->create();

        $moderator_valid = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        TrooperAssignment::factory()
            ->forTrooper($moderator_valid)
            ->forOrganization($root)
            ->asModerator()
            ->create();

        $moderator_invalid = Trooper::factory()->asModerator()->withInvalidEmail()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $query): bool
            {
                return $query instanceof GetTroopersByRoleQuery
                    && $query->membership_role === MembershipRole::ADMINISTRATOR;
            })
            ->andReturn(collect([$admin_valid, $admin_invalid]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $query): bool
            {
                return $query instanceof GetTroopersByRoleQuery
                    && $query->membership_role === MembershipRole::MODERATOR;
            })
            ->andReturn(collect([$moderator_valid, $moderator_invalid]));

        $subject = new SendTrooperRegisteredNotificationsJob($registered_trooper);
        $subject->handle($bus);

        Mail::assertQueued(TrooperAwaitingApproval::class, 2);
        Mail::assertQueued(TrooperAwaitingApproval::class, function (TrooperAwaitingApproval $mail) use ($admin_valid): bool
        {
            return $mail->hasTo($admin_valid->email);
        });
        Mail::assertQueued(TrooperAwaitingApproval::class, function (TrooperAwaitingApproval $mail) use ($moderator_valid): bool
        {
            return $mail->hasTo($moderator_valid->email);
        });
    }
}
