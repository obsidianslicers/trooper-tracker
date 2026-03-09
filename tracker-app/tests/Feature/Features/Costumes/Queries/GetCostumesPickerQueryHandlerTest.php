<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Costumes\Queries;

use App\Features\Costumes\Queries\GetCostumesPickerQuery;
use App\Features\Costumes\Queries\GetCostumesPickerQueryHandler;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetCostumesPickerQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_costumes_sorted_by_name(): void
    {
        $allowed_organization = Organization::factory()->withName('Tatooine Garrison')->create();

        $costume_zeta = Costume::factory()->withName('Zeta Armor')->create();
        $costume_alpha = Costume::factory()->withName('Alpha Armor')->create();

        \App\Models\OrganizationCostume::factory()
            ->forOrganization($allowed_organization)
            ->forCostume($costume_zeta)
            ->withPrefix('TZ')
            ->create();

        \App\Models\OrganizationCostume::factory()
            ->forOrganization($allowed_organization)
            ->forCostume($costume_alpha)
            ->withPrefix('TA')
            ->create();

        $subject = new GetCostumesPickerQueryHandler();

        $result = $subject(new GetCostumesPickerQuery([$allowed_organization->{Organization::ID}]));

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['Alpha Armor', 'Zeta Armor'], $result->pluck(Costume::NAME)->all());
    }

    public function test_invoke_filters_organization_costumes_by_organization_ids(): void
    {
        $allowed_organization = Organization::factory()->withName('Mos Eisley Outpost')->create();
        $excluded_organization = Organization::factory()->withName('Cloud City Wing')->create();

        $costume_with_mixed_orgs = Costume::factory()->withName('Stormtrooper')->create();
        $costume_only_excluded_org = Costume::factory()->withName('Scout Trooper')->create();

        \App\Models\OrganizationCostume::factory()
            ->forOrganization($allowed_organization)
            ->forCostume($costume_with_mixed_orgs)
            ->withPrefix('ME')
            ->create();

        \App\Models\OrganizationCostume::factory()
            ->forOrganization($excluded_organization)
            ->forCostume($costume_with_mixed_orgs)
            ->withPrefix('CC')
            ->create();

        \App\Models\OrganizationCostume::factory()
            ->forOrganization($excluded_organization)
            ->forCostume($costume_only_excluded_org)
            ->withPrefix('CCX')
            ->create();

        $subject = new GetCostumesPickerQueryHandler();

        $result = $subject(new GetCostumesPickerQuery([$allowed_organization->{Organization::ID}]));

        $mixed = $result->firstWhere(Costume::ID, $costume_with_mixed_orgs->{Costume::ID});
        $excluded_only = $result->firstWhere(Costume::ID, $costume_only_excluded_org->{Costume::ID});

        $this->assertNotNull($mixed);
        $this->assertNotNull($excluded_only);

        $this->assertCount(1, $mixed->organization_costumes);
        $this->assertSame(
            $allowed_organization->{Organization::ID},
            $mixed->organization_costumes->first()->organization->{Organization::ID}
        );
        $this->assertCount(0, $excluded_only->organization_costumes);
    }

    public function test_invoke_with_multiple_organization_ids_loads_matching_relations(): void
    {
        $first_organization = Organization::factory()->withName('Outer Rim Detachment')->create();
        $second_organization = Organization::factory()->withName('Core Worlds Regiment')->create();

        $costume = Costume::factory()->withName('Tie Pilot')->create();

        \App\Models\OrganizationCostume::factory()
            ->forOrganization($first_organization)
            ->forCostume($costume)
            ->withPrefix('OR')
            ->create();

        \App\Models\OrganizationCostume::factory()
            ->forOrganization($second_organization)
            ->forCostume($costume)
            ->withPrefix('CW')
            ->create();

        $subject = new GetCostumesPickerQueryHandler();

        $result = $subject(
            new GetCostumesPickerQuery([
                $first_organization->{Organization::ID},
                $second_organization->{Organization::ID},
            ])
        );

        $loaded_costume = $result->firstWhere(Costume::ID, $costume->{Costume::ID});

        $this->assertNotNull($loaded_costume);
        $this->assertCount(2, $loaded_costume->organization_costumes);
        $this->assertTrue($loaded_costume->relationLoaded('organization_costumes'));
        $this->assertTrue($loaded_costume->organization_costumes->first()->relationLoaded('organization'));
    }
}
