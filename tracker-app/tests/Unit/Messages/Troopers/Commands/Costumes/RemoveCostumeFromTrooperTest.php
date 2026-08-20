<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Costumes;

use App\Messages\Troopers\Commands\Costumes\RemoveCostumeFromTrooper;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveCostumeFromTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_deletes_only_matching_trooper_costumes_for_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $costume = Costume::factory()->create(['name' => 'Alpha Boots']);
        $other_costume = Costume::factory()->create(['name' => 'Beta Boots']);

        $matching_org_costume = OrganizationCostume::factory()
            ->forCostume($costume)
            ->forOrganization($organization)
            ->create();

        $other_org_costume = OrganizationCostume::factory()
            ->forCostume($other_costume)
            ->forOrganization($organization)
            ->create();

        $matching_trooper_costume = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($matching_org_costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($other_org_costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($other_trooper)
            ->forOrganizationCostume($matching_org_costume)
            ->create();

        $subject = new RemoveCostumeFromTrooper($trooper, $costume->id);

        $subject->handle();

        $this->assertSoftDeleted('tt_trooper_costumes', ['id' => $matching_trooper_costume->id]);
        $this->assertDatabaseHas('tt_trooper_costumes', ['id' => $other_org_costume->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('tt_trooper_costumes', ['trooper_id' => $other_trooper->id, 'organization_costume_id' => $matching_org_costume->id, 'deleted_at' => null]);
    }
}
