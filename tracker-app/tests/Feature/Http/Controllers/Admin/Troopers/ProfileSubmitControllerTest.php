<?php

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_as_admin_updates_trooper_and_redirects(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        $trooper_to_update = Trooper::factory()->create();
        $update_data = [
            'name' => 'Updated Name',
            'email' => 'updated@email.com',
            'phone' => '1234567890',
            'membership_status' => MembershipStatus::ACTIVE->value,
        ];

        // Act
        $response = $this->actingAs($admin_user)
            ->post(route('admin.troopers.profile', $trooper_to_update), $update_data);

        // Assert
        $response->assertRedirect(route('admin.troopers.list'));

        $this->assertDatabaseHas(Trooper::class, [
            'id' => $trooper_to_update->id,
            'name' => 'Updated Name',
            'email' => 'updated@email.com',
            'phone' => '1234567890',
            'membership_status' => MembershipStatus::ACTIVE->value,
        ]);
    }

    public function test_invoke_as_moderator_updates_moderated_trooper(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $moderator_user = Trooper::factory()->asModerator()->withAssignment($unit, moderator: true)->create();
        $trooper_to_update = Trooper::factory()->withAssignment($unit, member: true)->create();
        $update_data = [
            'name' => 'Moderated Update',
            'email' => $trooper_to_update->email, // Email must be unique
            'phone' => '9876543210',
            'membership_status' => MembershipStatus::ACTIVE->value,
        ];

        // Act
        $response = $this->actingAs($moderator_user)
            ->post(route('admin.troopers.profile', $trooper_to_update), $update_data);

        // Assert
        $response->assertRedirect(route('admin.troopers.list'));
        $this->assertDatabaseHas(Trooper::class, [
            'id' => $trooper_to_update->id,
            'name' => 'Moderated Update',
            'phone' => '9876543210',
        ]);
    }

    public function test_invoke_validation_fails_for_invalid_data(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        $trooper_to_update = Trooper::factory()->create();
        $update_data = [
            'name' => '', // Invalid name
            'email' => 'not-an-email', // Invalid email
        ];

        // Act
        $response = $this->actingAs($admin_user)
            ->post(route('admin.troopers.profile', $trooper_to_update), $update_data);

        // Assert
        $response->assertSessionHasErrors(['name', 'email']);
        $this->assertDatabaseMissing(Trooper::class, ['email' => 'not-an-email']);
    }
}