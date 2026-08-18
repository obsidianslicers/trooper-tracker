<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AddCostumeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_adds_costume_for_selected_organizations_and_renders_account_index(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->{Organization::ID},
            OrganizationCostume::COSTUME_ID => $costume->{Costume::ID},
        ]);
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $response = $this->actingAs($trooper)->post(route('account.add-costume'), [
            'costume_id' => $costume->{Costume::ID},
            'organization_ids' => [$organization->{Organization::ID}],
        ]);

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
        );

        $this->assertDatabaseHas('tt_trooper_costumes', [
            'trooper_id' => $trooper->{Trooper::ID},
            'organization_costume_id' => $organization_costume->{OrganizationCostume::ID},
        ]);
    }
}
