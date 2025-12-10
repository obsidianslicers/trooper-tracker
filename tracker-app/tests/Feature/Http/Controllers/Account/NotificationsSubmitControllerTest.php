<?php

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\FlashMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_settings_and_redirects(): void
    {
        // Arrange
        $unit_to_subscribe = Organization::factory()->unit()->create();
        $region_to_unsubscribe = $unit_to_subscribe->parent;
        $org_to_subscribe = $region_to_unsubscribe->parent;

        $trooper = Trooper::factory()
            ->withAssignment($region_to_unsubscribe, notify: true)
            ->create([
                'instant_notification' => false,
                'attendance_notification' => true,
                'command_staff_notification' => false,
            ]);

        $update_data = [
            'instant_notification' => '1',
            // 'attendance_notification' is not sent, so it should become false
            'command_staff_notification' => '1',
            'organizations' => [
                $org_to_subscribe->id => ['notification' => '1'],
            ],
            'units' => [
                $unit_to_subscribe->id => ['notification' => '1'],
            ],
            // 'regions' is not sent for $region_to_unsubscribe, so it should be false
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), $update_data);

        // Assert
        $response->assertRedirect(route('account.notifications'));

        // Assert global notification flags were updated
        $this->assertDatabaseHas(Trooper::class, [
            'id' => $trooper->id,
            'instant_notification' => true,
            'attendance_notification' => false,
            'command_staff_notification' => true,
        ]);

        // Assert organization notification assignments were updated
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $org_to_subscribe->id,
            'can_notify' => true,
        ]);
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $unit_to_subscribe->id,
            'can_notify' => true,
        ]);
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $region_to_unsubscribe->id,
            'can_notify' => false,
        ]);
    }

    public function test_invoke_as_guest_redirects_to_login(): void
    {
        // Arrange
        // No user is authenticated

        // Act
        $response = $this->post(route('account.notifications'), []);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}