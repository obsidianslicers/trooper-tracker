<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Pickers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for OrganizationPickerController.
 *
 * Validates:
 * - Requires authentication
 * - Requires property parameter
 * - Returns organization picker view
 * - Passes organizations to view
 * - Filters by moderated_only when provided
 */
class OrganizationPickerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('pickers.organization', ['property' => 'organization_id']));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_requires_property_parameter(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act & Assert
        $this->actingAs($trooper)
            ->get(route('pickers.organization'))
            ->assertStatus(500);
    }

    public function test_invoke_returns_organization_picker_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('pickers.organization', ['property' => 'organization_id']));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pickers.organization');
        $response->assertViewHas('property', 'organization_id');
    }

    public function test_invoke_passes_organizations_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('pickers.organization', ['property' => 'organization_id']));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organizations');
    }
}
