<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\NotificationFrequency;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for NotificationsController.
 *
 * Verifies:
 * - Authenticated troopers can view their notification settings
 * - Organizations are properly loaded with hierarchical structure
 * - Selected organizations are marked based on should_notify assignments
 * - Notification frequency is included in view data
 * - Unauthenticated users are redirected to login
 * - View is rendered with correct data structure
 */
class NotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_notification_settings_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.account.notifications');
    }

    public function test_invoke_passes_organizations_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $response->assertViewHas('organizations');
    }

    public function test_invoke_passes_notification_frequency_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $response->assertViewHas('notification_frequency', NotificationFrequency::DAILY);
    }

    public function test_invoke_marks_organizations_with_enabled_notifications_as_selected(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org_enabled = Organization::factory()->create();
        $org_disabled = Organization::factory()->create();

        // Create assignment with notifications enabled
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org_enabled->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        // Create assignment with notifications disabled
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org_disabled->id,
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');

        $enabled_org = $organizations->firstWhere('id', $org_enabled->id);
        $this->assertTrue($enabled_org->selected);

        $disabled_org = $organizations->firstWhere('id', $org_disabled->id);
        $this->assertFalse($disabled_org->selected);
    }

    public function test_invoke_marks_organizations_without_assignments_as_not_selected(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        // No assignment created

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');
        $org = $organizations->firstWhere('id', $organization->id);
        $this->assertFalse($org->selected);
    }

    public function test_invoke_handles_hierarchical_organization_structure(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Create org -> region -> unit hierarchy
        $parent_org = Organization::factory()->create();
        $region = Organization::factory()->create([
            Organization::PARENT_ID => $parent_org->id,
        ]);
        $unit = Organization::factory()->create([
            Organization::PARENT_ID => $region->id,
        ]);

        // Enable notifications for region only
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::IS_MEMBER => false,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');
        $org = $organizations->firstWhere('id', $parent_org->id);

        $this->assertFalse($org->selected);

        $region_obj = $org->organizations->firstWhere('id', $region->id);
        $this->assertTrue($region_obj->selected);

        $unit_obj = $region_obj->organizations->firstWhere('id', $unit->id);
        $this->assertFalse($unit_obj->selected);
    }

    public function test_invoke_handles_multiple_selected_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        // Enable notifications for org1 and org3
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org3->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');

        $this->assertTrue($organizations->firstWhere('id', $org1->id)->selected);
        $this->assertFalse($organizations->firstWhere('id', $org2->id)->selected);
        $this->assertTrue($organizations->firstWhere('id', $org3->id)->selected);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('account.notifications'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_correct_notification_frequency_for_never(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::NEVER,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $response->assertViewHas('notification_frequency', NotificationFrequency::NEVER);
    }

    public function test_invoke_displays_correct_notification_frequency_for_instant(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $response->assertViewHas('notification_frequency', NotificationFrequency::INSTANT);
    }

    public function test_invoke_displays_correct_notification_frequency_for_daily(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $response->assertViewHas('notification_frequency', NotificationFrequency::DAILY);
    }

    public function test_invoke_loads_organizations_with_fully_loaded_scope(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Create hierarchical structure
        $parent = Organization::factory()->create();
        $child = Organization::factory()->create([
            Organization::PARENT_ID => $parent->id,
        ]);
        $grandchild = Organization::factory()->create([
            Organization::PARENT_ID => $child->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');

        // Verify hierarchical loading
        $parent_org = $organizations->firstWhere('id', $parent->id);
        $this->assertNotNull($parent_org->organizations);

        $child_org = $parent_org->organizations->firstWhere('id', $child->id);
        $this->assertNotNull($child_org->organizations);

        $grandchild_org = $child_org->organizations->firstWhere('id', $grandchild->id);
        $this->assertNotNull($grandchild_org);
    }

    public function test_invoke_only_marks_assignments_with_should_notify_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        // Create assignments with different should_notify values
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);

        // org3 has no assignment

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');

        // Only org1 should be selected
        $this->assertTrue($organizations->firstWhere('id', $org1->id)->selected);
        $this->assertFalse($organizations->firstWhere('id', $org2->id)->selected);
        $this->assertFalse($organizations->firstWhere('id', $org3->id)->selected);
    }

    public function test_invoke_handles_trooper_with_no_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // No assignments created

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');

        foreach ($organizations as $org)
        {
            $this->assertFalse($org->selected);
        }
    }

    public function test_invoke_includes_all_organizations_regardless_of_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        // Only create assignment for org2
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $organizations = $response->viewData('organizations');

        // All organizations should be in the list
        $this->assertNotNull($organizations->firstWhere('id', $org1->id));
        $this->assertNotNull($organizations->firstWhere('id', $org2->id));
        $this->assertNotNull($organizations->firstWhere('id', $org3->id));
    }

    public function test_invoke_returns_view_instance(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notifications'));

        // Assert
        $this->assertInstanceOf(\Illuminate\View\View::class, $response->original);
    }
}
