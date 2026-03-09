<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_organization_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create(['name' => 'Old Org']);

        $response = $this->actingAs($trooper)->post('/admin/organizations/' . $organization->id . '/update', [
            'name' => 'New Org',
        ]);

        $response->assertRedirect(route('admin.organizations.list'));
        $this->assertDatabaseHas('tt_organizations', [
            'id' => $organization->id,
            'name' => 'New Org',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->post('/admin/organizations/' . $organization->id . '/update', [
            'name' => 'New Org',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
