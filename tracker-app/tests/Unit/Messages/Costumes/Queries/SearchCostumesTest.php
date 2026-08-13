<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Queries;

use App\Enums\MembershipStatus;
use App\Messages\Costumes\Queries\SearchCostumes;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SearchCostumesTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_filters_by_search_term(): void
    {
        Costume::factory()->withName('Zeta Armor')->create();
        Costume::factory()->withName('Alpha Armor')->create();
        Costume::factory()->withName('Scout Trooper')->create();

        $subject = new SearchCostumes('Armor', null);

        $result = $subject->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEqualsCanonicalizing(['Alpha Armor', 'Zeta Armor'], $result->pluck(Costume::NAME)->all());
    }

    public function test_handle_trims_search_term_before_filtering(): void
    {
        Costume::factory()->withName('Alpha Armor')->create();
        Costume::factory()->withName('Beta Armor')->create();

        $subject = new SearchCostumes('  alpha  ', null);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Alpha Armor', $result->first()->{Costume::NAME});
    }

    public function test_handle_returns_all_matching_costumes_when_trooper_is_null(): void
    {
        Costume::factory()->withName('Zeta Armor')->create();
        Costume::factory()->withName('Alpha Armor')->create();
        Costume::factory()->withName('Scout Trooper')->create();

        $subject = new SearchCostumes('Armor', null);

        $result = $subject->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEqualsCanonicalizing(['Alpha Armor', 'Zeta Armor'], $result->pluck(Costume::NAME)->all());
    }

    public function test_handle_limits_results_to_active_membership_organizations_when_trooper_is_provided(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $allowed_organization = Organization::factory()->withName('Core Worlds Detachment')->create();
        $inactive_organization = Organization::factory()->withName('Outer Rim Brigade')->create();

        $matching_costume = Costume::factory()->withName('Alpha Armor')->create();
        $other_costume = Costume::factory()->withName('Beta Armor')->create();
        $outside_costume = Costume::factory()->withName('Gamma Armor')->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($allowed_organization)
            ->state([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE])
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($inactive_organization)
            ->state([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED])
            ->create();

        OrganizationCostume::factory()
            ->forOrganization($allowed_organization)
            ->forCostume($matching_costume)
            ->withPrefix('CW')
            ->create();

        OrganizationCostume::factory()
            ->forOrganization($allowed_organization)
            ->forCostume($other_costume)
            ->withPrefix('CW2')
            ->create();

        OrganizationCostume::factory()
            ->forOrganization($inactive_organization)
            ->forCostume($outside_costume)
            ->withPrefix('OR')
            ->create();

        $subject = new SearchCostumes('Armor', $trooper);

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(
            ['Alpha Armor', 'Beta Armor'],
            $result->pluck(Costume::NAME)->all(),
        );
        $this->assertNotNull($result->firstWhere(Costume::ID, $matching_costume->{Costume::ID}));
        $this->assertNull($result->firstWhere(Costume::ID, $outside_costume->{Costume::ID}));
    }
}
