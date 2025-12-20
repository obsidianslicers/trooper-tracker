<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_invoke_updates_award_for_authorized_user(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create([
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Award Name',
            '_token' => 'test-token',
        ];

        // Act
        $response = $this->withSession(['_token' => 'test-token'])->post(route('admin.awards.update', $award), $data);

        // Assert
        $award->refresh();
        $this->assertEquals('Updated Award Name', $award->name);
    }

    public function test_invoke_validates_required_fields(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $data = ['_token' => 'test-token']; // Missing required fields

        // Act
        $response = $this->withSession(['_token' => 'test-token'])->post(route('admin.awards.update', $award), $data);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['name']);
    }

    public function test_invoke_denies_access_for_unauthorized_user(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $data = [
            'name' => 'Updated Award',
            'organization_id' => $organization->id,
            '_token' => 'test-token',
        ];

        // Act
        $response = $this->withSession(['_token' => 'test-token'])->post(route('admin.awards.update', $award), $data);

        // Assert
        $response->assertForbidden();
    }
}