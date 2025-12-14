<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_setup_page_for_authenticated_user(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        // Act
        $response = $this->get(route('account.setup'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.account.setup');
        $response->assertViewHas('trooper', fn(Trooper $view_trooper) => $view_trooper->is($trooper));
        $response->assertViewHas('organizations');
    }

    public function test_invoke_displays_organizations_with_selection_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();
        $trooper->organizations()->attach($organization->id, ['identifier' => 'TK-1']);

        // Act
        $response = $this->get(route('account.setup'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organizations', function ($organizations)
        {
            return $organizations->count() > 0;
        });
    }

    public function test_invoke_displays_organization_with_region_and_unit_from_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();
        $region = Organization::factory()->create([
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        $unit = Organization::factory()->create([
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        $trooper->organizations()->attach($organization->id, ['identifier' => 'TK-1']);
        $trooper->trooper_assignments()->create([
            'organization_id' => $unit->id,
            'is_member' => true,
        ]);

        // Act
        $response = $this->get(route('account.setup'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organizations', function ($organizations) use ($organization, $region, $unit)
        {
            $found = $organizations->firstWhere('id', $organization->id);
            return $found
                && $found->selected === true
                && $found->region?->is($region)
                && $found->unit?->is($unit);
        });
    }

    public function test_invoke_shows_region_only_when_no_unit_assigned(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();
        $region = Organization::factory()->create([
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);

        $trooper->organizations()->attach($organization->id, ['identifier' => 'TK-1']);
        $trooper->trooper_assignments()->create([
            'organization_id' => $region->id,
            'is_member' => true,
        ]);

        // Act
        $response = $this->get(route('account.setup'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organizations', function ($organizations) use ($organization, $region)
        {
            $found = $organizations->firstWhere('id', $organization->id);
            return $found
                && $found->selected === true
                && $found->region?->is($region)
                && !isset($found->unit);
        });
    }

    public function test_invoke_redirects_unauthenticated_user(): void
    {
        // Act
        $response = $this->get(route('account.setup'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
