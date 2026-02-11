<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DenialSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->post(route('admin.troopers.deny-htmx', $trooper));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_deny_trooper(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.deny-htmx', $trooper));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_updates_trooper_to_denied_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $this->actingAs($admin)->post(route('admin.troopers.deny-htmx', $trooper));

        // Assert
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::DENIED->value,
        ]);
    }

    public function test_invoke_returns_danger_flash_message(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.deny-htmx', $trooper));

        // Assert
        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Flash-Message'));
    }

    public function test_invoke_moderator_can_deny_pending_trooper(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $trooper = Trooper::factory()->asPending()->withAssignment($organization)->create();

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.troopers.deny-htmx', $trooper));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_regular_trooper_cannot_deny(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($trooper1)->post(route('admin.troopers.deny-htmx', $trooper2));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_can_deny_already_active_trooper(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.deny-htmx', $trooper));

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::DENIED->value,
        ]);
    }
}
