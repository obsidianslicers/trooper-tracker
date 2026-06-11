<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\Issues;

use App\Enums\TrooperRequestStatus;
use App\Enums\MembershipStatus;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Database\Seeders\Issues\Fix242;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Fix242Test extends TestCase
{
    use RefreshDatabase;

    public function test_run_migrates_pending_membership_to_join_request_at_selected_assignment(): void
    {
        $trooper = Trooper::factory()->asPending()->create();
        [$primary, $region, $unit] = $this->createOrganizationHierarchy();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary)
            ->withIdentifier('TK-12345')
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($unit)
            ->asMember()
            ->create();

        $this->seed(Fix242::class);

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $unit->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $primary->id,
            TrooperRequest::IDENTIFIER => 'TK-12345',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
        $this->assertSoftDeleted('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $primary->id,
        ]);
        $this->assertSoftDeleted('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $unit->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);
    }

    public function test_run_denies_pending_join_requests_for_denied_troopers(): void
    {
        $trooper = Trooper::factory()->asPending()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::DENIED,
        ]);
        [$primary] = $this->createOrganizationHierarchy();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary)
            ->forPrimaryOrganization($primary)
            ->create();

        $this->seed(Fix242::class);

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $primary->id,
            TrooperRequest::STATUS => TrooperRequestStatus::DENIED->value,
        ]);
        $this->assertNotNull(TrooperRequest::first()->updated_at);
    }

    public function test_run_repairs_active_sub_org_membership_with_notification_lineage(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        [$primary, $region, $unit] = $this->createOrganizationHierarchy();

        DB::table('tt_trooper_organizations')->insert([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $unit->id,
            TrooperOrganization::IDENTIFIER => 'TK-12345',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
            TrooperOrganization::CREATED_AT => now(),
            TrooperOrganization::UPDATED_AT => now(),
        ]);

        DB::table('tt_trooper_assignments')->insert([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $primary->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::SHOULD_NOTIFY => false,
            TrooperAssignment::IS_MODERATOR => false,
            TrooperAssignment::CREATED_AT => now(),
            TrooperAssignment::UPDATED_AT => now(),
        ]);

        $this->seed(Fix242::class);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $primary->id,
            TrooperOrganization::IDENTIFIER => 'TK-12345',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
        $this->assertSoftDeleted('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $unit->id,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $unit->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $primary->id,
            TrooperAssignment::IS_MEMBER => false,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::IS_MEMBER => false,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }

    /**
     * @return array{Organization, Organization, Organization}
     */
    private function createOrganizationHierarchy(): array
    {
        $primary = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->create();
        $region = Organization::factory()
            ->asRegion()
            ->withParent($primary)
            ->withNodePath('100:200:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withNodePath('100:200:300:')
            ->create();

        return [$primary, $region, $unit];
    }
}
