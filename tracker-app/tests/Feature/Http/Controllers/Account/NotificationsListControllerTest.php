<?php

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_as_authenticated_user_returns_view_with_correct_data(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'instant_notification' => true,
            'attendance_notification' => false,
        ]);

        $subscribed_unit = Organization::factory()->unit()->create();
        $unsubscribed_unit = Organization::factory()->unit()->create();

        // Create a notification subscription for the trooper
        TrooperAssignment::factory()->for($trooper)->for($subscribed_unit)->create(['notify' => true]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.account.notifications');

        $response->assertViewHas('instant_notification', true);
        $response->assertViewHas('attendance_notification', false);

        $response->assertViewHas('organizations', function ($organizations) use ($subscribed_unit, $unsubscribed_unit)
        {
            $subscribed_found = false;
            $unsubscribed_found_and_is_false = false;

            // Helper to recursively search for a unit and check its 'selected' status
            $find_unit = function ($orgs, $unit_id) use (&$find_unit)
            {
                foreach ($orgs as $org)
                {
                    if ($org->id === $unit_id)
                    {
                        return $org;
                    }
                    if ($org->organizations->isNotEmpty())
                    {
                        $found = $find_unit($org->organizations, $unit_id);
                        if ($found)
                        {
                            return $found;
                        }
                    }
                }
                return null;
            };

            $found_subscribed = $find_unit($organizations, $subscribed_unit->id);
            $subscribed_found = $found_subscribed && $found_subscribed->selected === true;

            $found_unsubscribed = $find_unit($organizations, $unsubscribed_unit->id);
            $unsubscribed_found_and_is_false = $found_unsubscribed && $found_unsubscribed->selected === false;

            return $subscribed_found && $unsubscribed_found_and_is_false;
        });
    }

    public function test_invoke_as_guest_redirects_to_login(): void
    {
        // Act & Assert
        $this->get(route('account.notifications'))
            ->assertRedirect(route('auth.login'));
    }
}