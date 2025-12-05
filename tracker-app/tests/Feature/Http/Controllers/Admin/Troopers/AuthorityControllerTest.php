<?php

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_as_admin_returns_view(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdministrator()->create();
        $trooper_to_view = Trooper::factory()->create();
        Organization::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($admin_user)
            ->get(route('admin.troopers.authority', $trooper_to_view));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.authority');
        $response->assertViewHas('trooper', $trooper_to_view);
        $response->assertViewHas('organization_authorities', function ($authorities)
        {
            return $authorities->count() === 3;
        });
    }

    public function test_invoke_as_moderator_is_forbidden(): void
    {
        // Arrange
        $moderator_user = Trooper::factory()->asModerator()->create();
        $trooper_to_view = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($moderator_user)
            ->get(route('admin.troopers.authority', $trooper_to_view));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_without_authentication_is_redirected(): void
    {
        // Arrange
        $trooper_to_view = Trooper::factory()->create();

        // Act & Assert
        $this->get(route('admin.troopers.authority', $trooper_to_view))
            ->assertRedirect(route('auth.login'));
    }
}