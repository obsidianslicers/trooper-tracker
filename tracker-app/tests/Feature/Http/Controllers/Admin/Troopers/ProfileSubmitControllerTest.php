<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->post(route('admin.troopers.profile', $trooper));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_post_profile_update(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::LEGAL_NAME => 'Original',
        ]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.profile', $trooper), [
            'legal_name' => 'Updated',
            'display_name' => $trooper->display_name,
            'email' => $trooper->email,
        ]);

        // Assert
        $response->assertRedirect();
    }

    public function test_invoke_moderator_cannot_update_trooper_profile(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.troopers.profile', $trooper), [
            'legal_name' => 'Updated',
            'display_name' => $trooper->display_name,
            'email' => $trooper->email,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_profile(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper1)->post(route('admin.troopers.profile', $trooper2), [
            'legal_name' => 'Updated',
            'display_name' => $trooper2->display_name,
            'email' => $trooper2->email,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_validates_required_fields(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.profile', $trooper), [
            'legal_name' => '',
            'display_name' => '',
            'email' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['legal_name', 'display_name', 'email']);
    }

    public function test_invoke_validates_email_format(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.profile', $trooper), [
            'legal_name' => 'First',
            'display_name' => 'Last',
            'email' => 'invalid-email',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }
}
