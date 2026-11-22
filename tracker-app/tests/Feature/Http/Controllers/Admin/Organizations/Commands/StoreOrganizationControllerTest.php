<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations\Commands;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOrganizationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_suborganization_and_redirects(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $parent = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)->post('/admin/organizations/'.$parent->id.'/children', [
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

        $response = $this->post('/admin/organizations/'.$parent->id.'/children', [
            'name' => 'New Region',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
