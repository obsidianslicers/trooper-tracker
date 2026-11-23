<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\Resources;

use App\Messages\Account\Resources\MembershipOrganizationCollection;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MembershipOrganizationCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_gives_each_club_with_only_the_ids_of_its_regions_and_units(): void
    {
        $organization = Organization::factory()->asOrganization()->withName('501st')
            ->withIdentifierDisplay('TK')->create();
        $region = Organization::factory()->asRegion()->withParent($organization)
            ->withName('Florida')->create();
        $unit = Organization::factory()->asUnit()->withParent($region)
            ->withName('Everglades')->create();

        $subject = new MembershipOrganizationCollection(GetOrganizationHierarchy::call());
        $result = $subject->toArray(new Request);

        $this->assertSame([
            [
                'id' => $organization->id,
                'name' => '501st',
                'identifier_display' => 'TK',
                'identifier_validation' => $organization->identifier_validation,
                'regions' => [
                    ['id' => $region->id, 'units' => [['id' => $unit->id]]],
                ],
            ],
        ], $result);
    }
}
