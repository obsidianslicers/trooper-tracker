<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_shows_award_troopers_list_for_authorized_user(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper = Trooper::factory()->create();
        AwardTrooper::factory()->for($award)->for($trooper)->create();

        // Act
        $response = $this->get(route('admin.awards.list-troopers', $award));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.list-troopers');
        $response->assertViewHas('award', $award);
        $response->assertViewHas('troopers');
    }

    public function test_invoke_shows_troopers_with_award_assignments(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();

        // Only trooper1 has the award
        AwardTrooper::factory()->for($award)->for($trooper1)->create();

        // Act
        $response = $this->get(route('admin.awards.list-troopers', $award));

        // Assert
        $response->assertOk();
        $troopers = $response->viewData('troopers');
        $this->assertTrue($troopers->contains($trooper1));
        $this->assertFalse($troopers->contains($trooper2));
    }

    public function test_invoke_denies_access_for_unauthorized_user(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        // Act
        $response = $this->get(route('admin.awards.list-troopers', $award));

        // Assert
        $response->assertForbidden();
    }
}