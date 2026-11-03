<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\RemoveTrooperMembership;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveTrooperMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_deletes_trooper_organization_record(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        (new RemoveTrooperMembership($trooper, $organization))->handle();

        $this->assertSoftDeleted('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_handle_clears_is_member_on_assignments_in_hierarchy(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $child = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($root)->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($child)->asMember()->create();

        (new RemoveTrooperMembership($trooper, $root))->handle();

        $this->assertSoftDeleted('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $child->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);
    }

    public function test_handle_does_not_affect_assignments_outside_hierarchy(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org_a)->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org_b)->asMember()->create();

        (new RemoveTrooperMembership($trooper, $org_a))->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org_b->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_handle_handles_no_assignments_gracefully(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        (new RemoveTrooperMembership($trooper, $organization))->handle();

        $this->assertSoftDeleted('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }
}
