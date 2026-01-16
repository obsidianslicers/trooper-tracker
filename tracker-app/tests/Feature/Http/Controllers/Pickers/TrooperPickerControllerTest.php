<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Pickers;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for TrooperPickerController.
 *
 * Validates:
 * - Requires authentication
 * - Requires property parameter
 * - Returns trooper picker view
 * - Passes troopers to view
 * - Filters by organization when provided
 * - Applies search filter when provided
 */
class TrooperPickerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('pickers.trooper', ['property' => 'trooper_id']));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_requires_property_parameter(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act & Assert
        $this->actingAs($trooper)
            ->get(route('pickers.trooper'))
            ->assertStatus(500);
    }

    public function test_invoke_returns_trooper_picker_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('pickers.trooper', ['property' => 'trooper_id']));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pickers.trooper');
        $response->assertViewHas('property', 'trooper_id');
    }

    public function test_invoke_passes_troopers_to_view(): void
    {
        // Arrange
        $auth_trooper = Trooper::factory()->asActive()->create();
        $trooper1 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice']);
        $trooper2 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Bob']);

        // Act
        $response = $this->actingAs($auth_trooper)
            ->get(route('pickers.trooper', ['property' => 'trooper_id']));

        // Assert
        $response->assertOk();
        $response->assertViewHas('troopers');
        $troopers = $response->viewData('troopers');
        $this->assertCount(3, $troopers);
    }

    public function test_invoke_filters_by_organization(): void
    {
        // Arrange
        $auth_trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $trooper1 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice']);
        $trooper2 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Bob']);
        $trooper3 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Charlie']);

        $trooper1->organizations()->attach($organization->id, ['identifier' => 'TK-001']);
        $trooper2->organizations()->attach($organization->id, ['identifier' => 'TK-002']);

        // Act
        $response = $this->actingAs($auth_trooper)
            ->get(route('pickers.trooper', [
                'property' => 'trooper_id',
                'organization_id' => $organization->id,
            ]));

        // Assert
        $response->assertOk();
        $troopers = $response->viewData('troopers');
        $this->assertCount(2, $troopers);
        $this->assertTrue($troopers->contains($trooper1));
        $this->assertTrue($troopers->contains($trooper2));
    }

    public function test_invoke_applies_search_filter(): void
    {
        // Arrange
        $auth_trooper = Trooper::factory()->asActive()->create();
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice Smith']);
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Bob Jones']);

        // Act
        $response = $this->actingAs($auth_trooper)
            ->get(route('pickers.trooper', [
                'property' => 'trooper_id',
                'search_term' => 'Bob',
            ]));

        // Assert
        $response->assertOk();
        $troopers = $response->viewData('troopers');
        $this->assertCount(1, $troopers);
        $this->assertEquals('Bob Jones', $troopers->first()->name);
    }

    public function test_invoke_passes_search_term_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('pickers.trooper', [
                'property' => 'trooper_id',
                'search_term' => 'test search',
            ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('search_term', 'test search');
    }
}
