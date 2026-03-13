<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_organization_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $parent = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)->get(route('admin.organizations.create', ['parent' => $parent->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.organizations.create');
    }

    public function test_invoke_requires_authentication(): void
    {
        $parent = Organization::factory()->asOrganization()->create();

        $response = $this->get(route('admin.organizations.create', ['parent' => $parent->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
