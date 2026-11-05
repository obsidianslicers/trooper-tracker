<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Costumes;

use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperCostumesForDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_trooper_costumes_excluding_command_staff_and_handler(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withName('Vader Fist')->create();

        $approved_costume = Costume::factory()->withName('Stormtrooper')->create();
        $approved_org_costume = OrganizationCostume::factory()->forOrganization($organization)->forCostume($approved_costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($approved_org_costume)->create();

        $command_staff = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $result = (new GetTrooperCostumesForDisplay($trooper))->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Stormtrooper', $result->first()->name);
        $this->assertSame('Vader Fist', $result->first()->costume_organizations);
        $this->assertNotSame($command_staff->id, $result->first()->id);
    }
}
