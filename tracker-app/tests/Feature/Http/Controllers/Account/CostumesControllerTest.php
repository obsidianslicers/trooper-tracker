<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostumesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_costumes_page_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $costume = Costume::factory()->create();

        OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.costumes'));

        $response->assertOk();
        $response->assertViewIs('pages.account.costumes');
        $response->assertViewHas('costumes');
        $response->assertViewHas('trooper_costumes');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.costumes'));

        $response->assertRedirect(route('auth.login'));
    }
}
