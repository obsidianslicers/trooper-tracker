<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\Issues;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Database\Seeders\Issues\Fix394;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Fix394Test extends TestCase
{
    use RefreshDatabase;

    public function test_run_retires_trooper_with_invalid_email_globally_and_in_every_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->withInvalidEmail()->create();
        $primary = Organization::factory()->create();
        $unit = Organization::factory()->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($primary)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($unit)->create();

        $this->seed(Fix394::class);

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $primary->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $unit->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
        ]);
    }

    public function test_run_leaves_trooper_with_valid_email_untouched(): void
    {
        $trooper = Trooper::factory()->asMember()->withEmail('valid@example.com')->create();
        $organization = Organization::factory()->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        $this->seed(Fix394::class);

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
    }

    public function test_run_does_not_retire_soft_deleted_organization_membership(): void
    {
        $trooper = Trooper::factory()->asMember()->withInvalidEmail()->create();
        $organization = Organization::factory()->create();

        $trooper_organization = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create();
        $trooper_organization->delete();

        $this->seed(Fix394::class);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
    }

    public function test_run_is_idempotent(): void
    {
        $trooper = Trooper::factory()->asMember()->withInvalidEmail()->create();
        $organization = Organization::factory()->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        $this->seed(Fix394::class);
        $this->seed(Fix394::class);

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
        ]);
    }
}
