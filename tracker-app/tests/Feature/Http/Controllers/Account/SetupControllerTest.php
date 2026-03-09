<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_setup_page_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.setup'));

        $response->assertOk();
        $response->assertViewIs('pages.account.setup');
        $response->assertViewHas('organization_memberships');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.setup'));

        $response->assertRedirect(route('auth.login'));
    }
}
