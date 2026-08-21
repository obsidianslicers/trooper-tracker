<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeTrooperOrganizations;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeTrooperOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_memberships_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_organization = Organization::factory()->asOrganization()->create();
        $trashed_organization = Organization::factory()->asOrganization()->create();

        $active_membership = TrooperOrganization::factory()
            ->forTrooper($source_trooper)
            ->forOrganization($active_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $trashed_membership = TrooperOrganization::factory()
            ->forTrooper($source_trooper)
            ->forOrganization($trashed_organization)
            ->withIdentifier('TK-54321')
            ->create();
        $trashed_membership->delete();

        MergeTrooperOrganizations::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::ID => $active_membership->id,
            TrooperOrganization::TROOPER_ID => $target_trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $active_organization->id,
            TrooperOrganization::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::ID => $trashed_membership->id,
            TrooperOrganization::TROOPER_ID => $target_trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $trashed_organization->id,
        ]);

        $this->assertSoftDeleted('tt_trooper_organizations', [
            TrooperOrganization::ID => $trashed_membership->id,
            TrooperOrganization::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::ID => $active_membership->id,
            TrooperOrganization::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::ID => $trashed_membership->id,
            TrooperOrganization::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_restores_target_membership_when_target_is_trashed_and_source_is_active(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $target_membership = TrooperOrganization::factory()
            ->forTrooper($target_trooper)
            ->forOrganization($organization)
            ->create();
        $target_membership->delete();

        $source_membership = TrooperOrganization::factory()
            ->forTrooper($source_trooper)
            ->forOrganization($organization)
            ->create();

        MergeTrooperOrganizations::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::ID => $target_membership->id,
            TrooperOrganization::TROOPER_ID => $target_trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::ID => $source_membership->id,
            TrooperOrganization::TROOPER_ID => $source_trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }
}
