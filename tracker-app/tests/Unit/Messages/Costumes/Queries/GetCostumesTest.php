<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Queries;

use App\Messages\Costumes\Queries\GetCostumes;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCostumesTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_all_costumes_sorted_by_name(): void
    {
        Costume::factory()->withName('Stormtrooper')->create();
        Costume::factory()->withName('Biker Scout')->create();

        $subject = new GetCostumes(null);

        $this->assertSame(
            ['Biker Scout', 'Stormtrooper'],
            $subject->handle()->pluck(Costume::NAME)->all()
        );
    }

    public function test_handle_limits_loaded_organization_costumes_to_given_organizations(): void
    {
        $costume = Costume::factory()->create();
        $kept = Organization::factory()->create();
        $skipped = Organization::factory()->create();
        OrganizationCostume::factory()->forCostume($costume)->forOrganization($kept)->create();
        OrganizationCostume::factory()->forCostume($costume)->forOrganization($skipped)->create();

        $subject = new GetCostumes([$kept->id]);
        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame(
            [$kept->id],
            $result->first()->organization_costumes->pluck(OrganizationCostume::ORGANIZATION_ID)->all()
        );
    }
}
