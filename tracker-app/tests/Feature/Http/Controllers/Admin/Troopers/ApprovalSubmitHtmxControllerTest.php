<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->post(route('admin.troopers.approve-htmx', $trooper));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_approve_trooper(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.approve-htmx', $trooper));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_updates_trooper_to_active_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $this->actingAs($admin)->post(route('admin.troopers.approve-htmx', $trooper));

        // Assert
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
    }

    public function test_invoke_returns_flash_message_header(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.approve-htmx', $trooper));

        // Assert
        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Flash-Message'));
    }

    public function test_invoke_moderator_cannot_approve_trooper_outside_scope(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.troopers.approve-htmx', $trooper));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_approve(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($trooper1)->post(route('admin.troopers.approve-htmx', $trooper2));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_can_approve_already_active_trooper(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.approve-htmx', $trooper));

        // Assert
        $response->assertOk();
    }
}
