<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignTroopersSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_assigns_award_to_selected_troopers(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();

        $awardDate = now()->format('Y-m-d');

        $data = [
            'trooper_ids' => [$trooper1->id, $trooper2->id],
            'award_date' => $awardDate,
        ];

        // Act
        $response = $this->post(route('admin.awards.assign-troopers', $award), $data);

        // Assert
        $response->assertRedirect(route('admin.awards.list-troopers', $award));

        $this->assertDatabaseHas(AwardTrooper::class, [
            'award_id' => $award->id,
            'trooper_id' => $trooper1->id,
            'award_date' => $awardDate . ' 00:00:00',
        ]);

        $this->assertDatabaseHas(AwardTrooper::class, [
            'award_id' => $award->id,
            'trooper_id' => $trooper2->id,
            'award_date' => $awardDate . ' 00:00:00',
        ]);
    }

    public function test_invoke_skips_existing_assignments(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper = Trooper::factory()->create();
        AwardTrooper::factory()->for($award)->for($trooper)->create();

        $data = [
            'trooper_ids' => [$trooper->id],
            'award_date' => now()->format('Y-m-d'),
        ];

        // Act
        $response = $this->post(route('admin.awards.assign-troopers', $award), $data);

        // Assert
        $response->assertRedirect(route('admin.awards.list-troopers', $award));

        // Should still have only one record
        $this->assertEquals(1, AwardTrooper::where('award_id', $award->id)->where('trooper_id', $trooper->id)->count());
    }

    public function test_invoke_validates_required_fields(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $data = []; // Missing required fields

        // Act
        $response = $this->post(route('admin.awards.assign-troopers', $award), $data);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['trooper_ids', 'award_date']);
    }

    public function test_invoke_denies_access_for_unauthorized_user(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $data = [
            'trooper_ids' => [],
            'award_date' => now()->format('Y-m-d'),
        ];

        // Act
        $response = $this->post(route('admin.awards.assign-troopers', $award), $data);

        // Assert
        $response->assertForbidden();
    }
}