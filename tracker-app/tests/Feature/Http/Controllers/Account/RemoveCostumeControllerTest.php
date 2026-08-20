<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RemoveCostumeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_removes_costume_and_returns_updated_trooper_costumes(): void
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

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();

        $response = $this->actingAs($trooper)->post(route('account.remove-costume'), [
            'costume_id' => $costume->{Costume::ID},
        ]);

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
            ->where('results.trooper_costumes', [])
        );

        $this->assertSoftDeleted('tt_trooper_costumes', [
            'trooper_id' => $trooper->{Trooper::ID},
            'organization_costume_id' => $organization_costume->{OrganizationCostume::ID},
        ]);
    }
}
