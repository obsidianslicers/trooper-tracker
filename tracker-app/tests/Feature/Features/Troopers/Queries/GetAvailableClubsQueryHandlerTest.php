<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetAvailableClubsQuery;
use App\Features\Troopers\Queries\GetAvailableClubsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see GetAvailableClubsQueryHandler
 */
class GetAvailableClubsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_organizations_excluding_active_memberships(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $org_a = Organization::factory()->asOrganization()->withName('Alpha')->create();
        $org_b = Organization::factory()->asOrganization()->withName('Bravo')->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org_a)->asMember()->create();

        $handler = app(GetAvailableClubsQueryHandler::class);
        $result = $handler(new GetAvailableClubsQuery($trooper));

        $this->assertCount(1, $result);
        $this->assertEquals($org_b->id, $result->first()->id);
    }

    public function test_invoke_returns_all_organizations_when_no_memberships(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        Organization::factory()->asOrganization()->count(3)->create();

        $handler = app(GetAvailableClubsQueryHandler::class);
        $result = $handler(new GetAvailableClubsQuery($trooper));

        $this->assertCount(3, $result);
    }

    public function test_invoke_returns_results_ordered_by_sequence(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        Organization::factory()->asOrganization()->withName('Zeta')->withSequence(30)->create();
        Organization::factory()->asOrganization()->withName('Alpha')->withSequence(10)->create();
        Organization::factory()->asOrganization()->withName('Gamma')->withSequence(20)->create();

        $handler = app(GetAvailableClubsQueryHandler::class);
        $result = $handler(new GetAvailableClubsQuery($trooper));

        $this->assertEquals(['Alpha', 'Gamma', 'Zeta'], $result->pluck(Organization::NAME)->all());
    }
}
