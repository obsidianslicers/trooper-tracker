<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperMembershipsQuery;
use App\Features\Troopers\Queries\GetTrooperMembershipsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperMembershipsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_identifier_and_assignment_for_matching_organizations(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->withName('Alpha')->create();
        $region = Organization::factory()->asRegion()->withParent($organization)->withNodePath('100.200.')->withName('Bravo')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->withIdentifier('TK-12345')->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($region)->asMember()->create();

        $subject = new GetTrooperMembershipsQueryHandler();

        $result = $subject(new GetTrooperMembershipsQuery($trooper));

        $matched = $result->firstWhere('id', $organization->id);

        $this->assertNotNull($matched);
        $this->assertSame('TK-12345', $matched->identifier);
        $this->assertSame($region->id, $matched->assignment->id);
    }
}
