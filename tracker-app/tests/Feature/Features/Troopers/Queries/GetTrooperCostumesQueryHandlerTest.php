<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperCostumesQueryHandler;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperCostumesQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_trooper_costumes_excluding_command_staff_and_handler(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withName('Vader Fist')->create();

        $approved_costume = Costume::factory()->withName('Stormtrooper')->create();
        $approved_org_costume = OrganizationCostume::factory()->forOrganization($organization)->forCostume($approved_costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($approved_org_costume)->create();

        $command_staff = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $subject = new GetTrooperCostumesQueryHandler();

        $result = $subject(new GetTrooperCostumesQuery($trooper));

        $this->assertCount(1, $result);
        $this->assertSame('Stormtrooper', $result->first()->name);
        $this->assertSame('Vader Fist', $result->first()->costume_organizations);
        $this->assertNotSame($command_staff->id, $result->first()->id);
    }
}
