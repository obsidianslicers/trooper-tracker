<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Mail\Admin\Troopers\TrooperJoinRequestSubmitted;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * @see \App\Jobs\SendJoinRequestNotificationsJob
 */
class SendJoinRequestNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_queues_mail_to_admins_with_valid_emails(): void
    {
        Mail::fake();

        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asMember()->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => \App\Enums\MembershipStatus::PENDING]);

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

        $subject = new SendJoinRequestNotificationsJob($join_request);
        $subject->handle($bus);

        Mail::assertQueued(TrooperJoinRequestSubmitted::class, 1);
        Mail::assertQueued(TrooperJoinRequestSubmitted::class, fn (TrooperJoinRequestSubmitted $mail): bool => $mail->hasTo($admin_valid->email));
    }

    public function test_handle_queues_mail_to_moderators_with_authority_over_request(): void
    {
        Mail::fake();

        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $child = Organization::factory()->asOrganization()->withNodePath('100:200:')->withParent($root)->create();

        $trooper = Trooper::factory()->asMember()->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($child)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => \App\Enums\MembershipStatus::PENDING]);

        $moderator_in_tree = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        TrooperAssignment::factory()->forTrooper($moderator_in_tree)->forOrganization($root)->asModerator()->create();

        $moderator_outside_tree = Trooper::factory()->asModerator()->withEmail('other-mod@example.com')->create();
        $other_org = Organization::factory()->asOrganization()->withNodePath('999:')->create();
        TrooperAssignment::factory()->forTrooper($moderator_outside_tree)->forOrganization($other_org)->asModerator()->create();

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

        $subject = new SendJoinRequestNotificationsJob($join_request);
        $subject->handle($bus);

        Mail::assertQueued(TrooperJoinRequestSubmitted::class, 1);
        Mail::assertQueued(TrooperJoinRequestSubmitted::class, fn (TrooperJoinRequestSubmitted $mail): bool => $mail->hasTo($moderator_in_tree->email));
        Mail::assertNotQueued(TrooperJoinRequestSubmitted::class, fn (TrooperJoinRequestSubmitted $mail): bool => $mail->hasTo($moderator_outside_tree->email));
    }

    public function test_handle_skips_moderators_with_invalid_emails(): void
    {
        Mail::fake();

        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asMember()->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => \App\Enums\MembershipStatus::PENDING]);

        $moderator_invalid = Trooper::factory()->asModerator()->withInvalidEmail()->create();
        TrooperAssignment::factory()->forTrooper($moderator_invalid)->forOrganization($organization)->asModerator()->create();

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
            ->andReturn(collect([$moderator_invalid]));

        $subject = new SendJoinRequestNotificationsJob($join_request);
        $subject->handle($bus);

        Mail::assertNothingQueued();
    }
}
