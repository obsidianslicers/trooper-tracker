<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations\Pages;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexOrganizationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_organizations_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        Organization::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.organizations.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/organizations/List')
            ->has('organizations'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.organizations.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
