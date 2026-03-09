<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_suborganization_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $parent = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)->post('/admin/organizations/' . $parent->id . '/create', [
            'name' => 'New Region',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_organizations', [
            'name' => 'New Region',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $parent = Organization::factory()->asOrganization()->create();

        $response = $this->post('/admin/organizations/' . $parent->id . '/create', [
            'name' => 'New Region',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
