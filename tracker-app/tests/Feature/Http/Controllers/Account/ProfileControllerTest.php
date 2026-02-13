<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ProfileController.
 *
 * Verifies:
 * - Authenticated troopers can view their profile page
 * - Trooper data is passed to the view
 * - Correct view is rendered
 * - Unauthenticated users are redirected to login
 * - View instance is returned
 */
class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_profile_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.account.profile');
    }

    public function test_invoke_passes_trooper_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertViewHas('trooper');
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals($trooper->id, $view_trooper->id);
    }

    public function test_invoke_passes_correct_trooper_instance_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::DISPLAY_NAME => 'John',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals('test@example.com', $view_trooper->email);
        $this->assertEquals('John', $view_trooper->display_name);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('account.profile'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_works_for_active_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_works_for_pending_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_works_for_retired_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_works_for_administrator(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertOk();
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals(MembershipRole::ADMINISTRATOR, $view_trooper->membership_role);
    }

    public function test_invoke_works_for_moderator(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->asModerator()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertOk();
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals(MembershipRole::MODERATOR, $view_trooper->membership_role);
    }

    public function test_invoke_includes_trooper_notification_preferences(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals(NotificationFrequency::DAILY, $view_trooper->notification_frequency);
    }

    public function test_invoke_includes_trooper_theme_preference(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::THEME => TrooperTheme::STORMTROOPER,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals(TrooperTheme::STORMTROOPER, $view_trooper->theme);
    }

    public function test_invoke_includes_all_trooper_attributes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'trooper@501st.com',
            Trooper::DISPLAY_NAME => 'Luke',
            Trooper::PHONE => '555-1234',
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals('trooper@501st.com', $view_trooper->email);
        $this->assertEquals('Luke', $view_trooper->display_name);
        $this->assertEquals('555-1234', $view_trooper->phone);
        $this->assertEquals(MembershipStatus::ACTIVE, $view_trooper->membership_status);
        $this->assertEquals(MembershipRole::MEMBER, $view_trooper->membership_role);
    }

    public function test_invoke_returns_view_instance(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $this->assertInstanceOf(\Illuminate\View\View::class, $response->original);
    }

    public function test_invoke_uses_authenticated_trooper_from_request(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create(['email' => 'trooper1@501st.com']);
        $trooper2 = Trooper::factory()->asActive()->create(['email' => 'trooper2@501st.com']);

        // Act - authenticate as trooper1
        $response = $this->actingAs($trooper1)
            ->get(route('account.profile'));

        // Assert - should see trooper1's data, not trooper2's
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals($trooper1->id, $view_trooper->id);
        $this->assertEquals('trooper1@501st.com', $view_trooper->email);
        $this->assertNotEquals($trooper2->id, $view_trooper->id);
    }

    public function test_invoke_view_has_only_trooper_data(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert - verify the view data structure
        $trooper = $response->viewData('trooper');

        // The view should have the trooper data
        $this->assertInstanceOf(Trooper::class, $trooper);
    }

    public function test_invoke_does_not_modify_trooper_data(): void
    {
        // Arrange
        $original_email = 'original@example.com';
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => $original_email,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert - trooper data should remain unchanged
        $trooper->refresh();
        $this->assertEquals($original_email, $trooper->email);
    }

    public function test_invoke_renders_correct_view_name(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $this->assertEquals('pages.account.profile', $response->original->name());
    }
}
