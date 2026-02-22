<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecord;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ServiceRecordDisplayController.
 *
 * Verifies:
 * - Authenticated troopers can view service record page
 * - Service record data is passed to the view correctly
 * - Optional trooper_id parameter allows viewing other troopers
 * - Breadcrumb is added when viewing own record
 * - Command Staff and Handler costumes are filtered out
 * - Tagged uploads are included in view data
 * - Correct view is rendered
 * - Unauthenticated users are redirected to login
 */
class ServiceRecordDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_service_record_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.service-record.display');
    }

    public function test_invoke_passes_trooper_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('trooper');
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals($trooper->id, $view_trooper->id);
    }

    public function test_invoke_loads_own_service_record_by_default(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DISPLAY_NAME => 'John Doe',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals('John Doe', $view_trooper->display_name);
        $this->assertEquals($trooper->id, $view_trooper->id);
    }

    public function test_invoke_loads_other_trooper_service_record_when_trooper_id_provided(): void
    {
        // Arrange
        $authenticated_trooper = Trooper::factory()->asActive()->create([
            Trooper::DISPLAY_NAME => 'Current User',
        ]);
        $other_trooper = Trooper::factory()->asActive()->create([
            Trooper::DISPLAY_NAME => 'Other User',
        ]);

        // Act
        $response = $this->actingAs($authenticated_trooper)
            ->get(route('service-record.display', ['trooper_id' => $other_trooper->id]));

        // Assert
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals('Other User', $view_trooper->display_name);
        $this->assertEquals($other_trooper->id, $view_trooper->id);
    }

    public function test_invoke_passes_trooper_costumes_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create([
            Costume::NAME => 'Stormtrooper',
        ]);
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('trooper_costumes');
        $trooper_costumes = $response->viewData('trooper_costumes');
        $this->assertCount(1, $trooper_costumes);
        $this->assertEquals('Stormtrooper', $trooper_costumes->first()->name);
    }

    public function test_invoke_filters_out_command_staff_costume(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $regular_costume = Costume::factory()->create([
            Costume::NAME => 'Stormtrooper',
        ]);
        $command_staff_costume = Costume::factory()->create([
            Costume::NAME => 'Command Staff',
        ]);
        $org_costume_regular = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $regular_costume->id,
        ]);
        $org_costume_command = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $command_staff_costume->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume_regular->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume_command->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $trooper_costumes = $response->viewData('trooper_costumes');
        $this->assertCount(1, $trooper_costumes);
        $this->assertEquals('Stormtrooper', $trooper_costumes->first()->name);
    }

    public function test_invoke_filters_out_handler_costume(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $regular_costume = Costume::factory()->create([
            Costume::NAME => 'Rebel Pilot',
        ]);
        $handler_costume = Costume::factory()->create([
            Costume::NAME => 'Handler',
        ]);
        $org_costume_regular = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $regular_costume->id,
        ]);
        $org_costume_handler = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $handler_costume->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume_regular->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume_handler->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $trooper_costumes = $response->viewData('trooper_costumes');
        $this->assertCount(1, $trooper_costumes);
        $this->assertEquals('Rebel Pilot', $trooper_costumes->first()->name);
    }

    public function test_invoke_filters_out_both_command_staff_and_handler(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $regular_costume = Costume::factory()->create([
            Costume::NAME => 'TIE Pilot',
        ]);
        $command_staff_costume = Costume::factory()->create([
            Costume::NAME => 'Command Staff',
        ]);
        $handler_costume = Costume::factory()->create([
            Costume::NAME => 'Handler',
        ]);
        $org_costume_regular = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $regular_costume->id,
        ]);
        $org_costume_command = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $command_staff_costume->id,
        ]);
        $org_costume_handler = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $handler_costume->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume_regular->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume_command->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume_handler->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $trooper_costumes = $response->viewData('trooper_costumes');
        $this->assertCount(1, $trooper_costumes);
        $this->assertEquals('TIE Pilot', $trooper_costumes->first()->name);
    }

    public function test_invoke_passes_service_summary_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('service_summary');
        $service_summary = $response->viewData('service_summary');
        $this->assertIsArray($service_summary);
    }

    public function test_invoke_passes_organizations_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        TrooperOrganization::factory()
            ->for($trooper)
            ->for($organization)
            ->create([
                TrooperOrganization::IDENTIFIER => 'TK-12345',
            ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('trooper_organizations');
        $organizations = $response->viewData('trooper_organizations');
        $this->assertCount(1, $organizations);
    }

    public function test_invoke_passes_upcoming_event_shifts_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('upcoming_shifts');
        $upcoming_shifts = $response->viewData('upcoming_shifts');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $upcoming_shifts);
    }

    public function test_invoke_passes_recent_event_shifts_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('recent_shifts');
        $recent_shifts = $response->viewData('recent_shifts');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recent_shifts);
    }

    public function test_invoke_passes_donations_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('recent_donations');
    }

    public function test_invoke_passes_awards_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('awards');
    }

    public function test_invoke_passes_tagged_uploads_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('service-record.display'));

        // Assert
        $response->assertViewHas('tagged_uploads');
        $tagged_uploads = $response->viewData('tagged_uploads');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tagged_uploads);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('service-record.display'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
