<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\TrooperRequestStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\ApproveTrooperRequestCommand;
use App\Features\Troopers\Commands\ApproveTrooperRequestCommandHandler;
use App\Features\Troopers\Exceptions\DuplicateOrganizationIdentifierException;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Notifications\Troopers\TrooperRequestApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see ApproveTrooperRequestCommandHandler
 */
class ApproveTrooperRequestCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_marks_join_request_as_approved(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $trooper_request->refresh();
        $this->assertEquals(TrooperRequestStatus::APPROVED, $trooper_request->status);
        $this->assertNotNull($trooper_request->updated_at);
    }

    public function test_invoke_creates_active_trooper_organization_at_primary_club(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function test_invoke_creates_trooper_assignment_at_requested_org(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $region = Organization::factory()->asRegion()->withParent($primary)->withNodePath('100:200:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->forPrimaryOrganization($primary)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $primary->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function test_invoke_creates_notification_assignments_for_requested_org_lineage(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $region = Organization::factory()->asRegion()->withParent($primary)->withNodePath('100:200:')->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->withNodePath('100:200:300:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($unit)
            ->forPrimaryOrganization($primary)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $primary->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::IS_MEMBER => false,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::IS_MEMBER => false,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $unit->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_clears_existing_member_assignment_in_same_hierarchy(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
        ]);
        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $stale_assignment_org = Organization::factory()
            ->asRegion()
            ->withParent($root)
            ->withNodePath('100:200:')
            ->create();

        // Insert directly to simulate stale historical data.
        DB::table('tt_trooper_assignments')->insert([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $stale_assignment_org->id,
            TrooperAssignment::IS_MEMBER => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($root)
            ->forPrimaryOrganization($root)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $this->assertSoftDeleted('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $stale_assignment_org->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);
    }

    public function test_invoke_sends_approved_notification_to_trooper(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        Notification::assertSentTo($trooper, TrooperRequestApprovedNotification::class);
    }

    public function test_invoke_does_not_send_notification_when_suppressed(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request, true));

        Notification::assertNothingSent();
    }

    public function test_invoke_persists_identifier_to_primary_club_trooper_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-99999')
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-99999',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function test_invoke_rejects_identifier_that_belongs_to_another_trooper_in_primary_club(): void
    {
        $existing_trooper = Trooper::factory()->asMember()->create();
        $pending_trooper = Trooper::factory()->asMember()->create();
        $primary_club = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withIdentifierDisplay('TKID')
            ->withNodePath('100:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withParent($primary_club)
            ->withNodePath('100:200:')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($primary_club)
            ->withIdentifier('1012')
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        $trooper_request = TrooperRequest::withoutEvents(fn() => TrooperRequest::factory()
            ->forTrooper($pending_trooper)
            ->forOrganization($unit)
            ->forPrimaryOrganization($primary_club)
            ->withIdentifier('1012')
            ->create());

        $this->expectException(DuplicateOrganizationIdentifierException::class);
        $this->expectExceptionMessage('501st Legion TKID 1012 is already assigned to another trooper.');

        $handler = app(ApproveTrooperRequestCommandHandler::class);

        try
        {
            $handler(new ApproveTrooperRequestCommand($trooper_request));
        }
        finally
        {
            $this->assertDatabaseHas('tt_trooper_requests', [
                TrooperRequest::ID => $trooper_request->id,
                TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
            ]);
            $this->assertDatabaseMissing('tt_trooper_organizations', [
                TrooperOrganization::TROOPER_ID => $pending_trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ]);
            $this->assertDatabaseMissing('tt_trooper_assignments', [
                TrooperAssignment::TROOPER_ID => $pending_trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $unit->id,
                TrooperAssignment::IS_MEMBER => true,
            ]);
        }
    }

    public function test_invoke_does_not_overwrite_primary_club_identifier_when_request_identifier_empty(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_club = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $region = Organization::factory()
            ->asRegion()
            ->withParent($primary_club)
            ->withNodePath('100:200:')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_club)
            ->withIdentifier('TK-11111')
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->forPrimaryOrganization($primary_club)
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            TrooperOrganization::IDENTIFIER => 'TK-11111',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function test_invoke_handles_f197_guardian_minor_data_setup(): void
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('Galactic Academy')
            ->withNodePath('100:')
            ->create();

        $region = Organization::factory()
            ->asRegion()
            ->withName('North America Coruscant Campus')
            ->withParent($organization)
            ->withNodePath('100:200:')
            ->create();

        $unit = Organization::factory()
            ->asUnit()
            ->withName('Florida Dagobah School')
            ->withParent($region)
            ->withNodePath('100:200:300:')
            ->create();

        $guardian = Trooper::factory()
            ->asMember()
            ->asActive()
            ->withEmail('guardian@sw.com')
            ->withDisplayName('Requires Guardian')
            ->withLegalName('Requires Guardian')
            ->withVerifiedEmail()
            ->withSetupCompleted()
            ->create();

        $child = Trooper::factory()
            ->asMember()
            ->asPending()
            ->withEmail('child@sw.com')
            ->withDisplayName('Child')
            ->withLegalName('Child')
            ->withGuardian($guardian)
            ->withVerifiedEmail()
            ->withSetupCompleted()
            ->create([
                Trooper::DATE_OF_BIRTH => now()->subYears(13),
            ]);

        TrooperAssignment::factory()
            ->forTrooper($child)
            ->forOrganization($unit)
            ->asMember()
            ->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($child)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('654321')
            ->create();

        $handler = app(ApproveTrooperRequestCommandHandler::class);
        $handler(new ApproveTrooperRequestCommand($trooper_request));

        $trooper_request->refresh();
        $this->assertEquals(TrooperRequestStatus::APPROVED, $trooper_request->status);
        $this->assertNotNull($trooper_request->updated_at);

        $this->assertSoftDeleted('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $child->id,
            TrooperAssignment::ORGANIZATION_ID => $unit->id,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $child->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $child->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => '654321',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }
}
