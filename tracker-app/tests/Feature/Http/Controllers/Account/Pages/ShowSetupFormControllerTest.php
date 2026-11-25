<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account\Pages;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowSetupFormControllerTest extends TestCase
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('account/Setup')
            ->where('trooper.email', $trooper->email)
            ->where('memberships.0.name', $organization->name)
            ->where('memberships.0.assignment', $organization->name));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.setup'));

        $response->assertRedirect(route('auth.login'));
    }
}
