<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_shows_create_form_for_authorized_user(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        // Act
        $response = $this->get(route('admin.awards.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.create');
    }

    public function test_invoke_denies_access_for_unauthorized_user(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('admin.awards.create'));

        // Assert
        $response->assertForbidden();
    }
}