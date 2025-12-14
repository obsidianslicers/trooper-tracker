<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_email_and_completion_timestamp(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();

        $data = [
            'email' => 'new@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('account.setup'), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));
        $trooper->refresh();
        $this->assertEquals('new@example.com', $trooper->email);
        $this->assertNotNull($trooper->setup_completed_at);
    }

    public function test_invoke_creates_membership_assignment_at_organization_level(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();

        $data = [
            'email' => 'org-' . time() . '@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => true,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('account.setup'), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $organization->id,
            'is_member' => true,
        ]);
    }

    public function test_invoke_creates_membership_assignment_at_region_level(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();
        $region = Organization::factory()->create([
            'parent_id' => $organization->id,
        ]);

        $data = [
            'email' => 'region-' . time() . '@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => true,
                    'region_id' => $region->id,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('account.setup'), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $region->id,
            'is_member' => true,
        ]);
    }

    public function test_invoke_creates_membership_assignment_at_unit_level(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();
        $region = Organization::factory()->create([
            'parent_id' => $organization->id,
        ]);
        $unit = Organization::factory()->create([
            'parent_id' => $region->id,
        ]);

        $data = [
            'email' => 'unit-' . time() . '@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => true,
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('account.setup'), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $unit->id,
            'is_member' => true,
        ]);
    }

    public function test_invoke_updates_existing_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $region = Organization::factory()->region()->create();
        $organization = $region->parent;

        $existing_assignment = $trooper->trooper_assignments()->create([
            'organization_id' => $region->id,
            'is_member' => false,
        ]);

        $data = [
            'email' => 'update@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'region_id' => $region->id,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('account.setup'), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));
        $existing_assignment->refresh();
        $this->assertEquals($region->id, $existing_assignment->organization_id);
        $this->assertTrue($existing_assignment->is_member);
    }

    public function test_invoke_skips_unselected_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();

        $data = [
            'email' => 'skip-' . time() . '@example.com',
            'organizations' => [
                $organization_1->id => [
                    'selected' => true,
                ],
                $organization_2->id => [
                    'selected' => false,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('account.setup'), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $organization_1->id,
        ]);
        $this->assertDatabaseMissing(TrooperAssignment::class, [
            'trooper_id' => $trooper->id,
            'organization_id' => $organization_2->id,
        ]);
    }

    public function test_invoke_redirects_unauthenticated_user(): void
    {
        // Act
        $response = $this->post(route('account.setup'), []);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
