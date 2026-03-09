<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_organization_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.organizations.update', ['organization' => $organization->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.organizations.update');
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->get(route('admin.organizations.update', ['organization' => $organization->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
