<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_invoke_creates_award_for_authorized_user(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();

        $data = [
            'name' => 'Test Award',
            'frequency' => 'once',
            'organization_id' => $organization->id,
        ];

        // Act
        $response = $this->withSession(['_token' => 'test-token'])->post(route('admin.awards.create'), $data + ['_token' => 'test-token']);

        // Assert
        $response->assertRedirect(route('admin.awards.list'));
        $this->assertDatabaseHas(Award::class, [
            'name' => 'Test Award',
            'frequency' => 'once',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_invoke_validates_required_fields(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $data = ['_token' => 'test-token']; // Missing required fields

        // Act
        $response = $this->withSession(['_token' => 'test-token'])->post(route('admin.awards.create'), $data);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'organization_id']);
    }

    public function test_invoke_denies_access_for_unauthorized_user(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();

        $data = [
            'name' => 'Test Award',
            'organization_id' => $organization->id,
            '_token' => 'test-token',
        ];

        // Act
        $response = $this->withSession(['_token' => 'test-token'])->post(route('admin.awards.create'), $data);

        // Assert
        $response->assertForbidden();
    }
}