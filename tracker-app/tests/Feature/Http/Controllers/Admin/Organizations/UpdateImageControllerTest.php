<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        // Act
        $response = $this->post(route('admin.organizations.update-image', $organization));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_upload_logo(): void
    {
        // Arrange
        Storage::fake('public');
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        $file = UploadedFile::fake()->image('logo.png');

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.update-image', $organization), [
            'logo' => $file,
        ]);

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_can_upload_logo_for_moderated_organization(): void
    {
        // Arrange
        Storage::fake('public');
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $file = UploadedFile::fake()->image('logo.png');

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.organizations.update-image', $organization), [
            'logo' => $file,
        ]);

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_cannot_upload_logo_for_non_moderated_organization(): void
    {
        // Arrange
        Storage::fake('public');
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $file = UploadedFile::fake()->image('logo.png');

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.organizations.update-image', $other_org), [
            'logo' => $file,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_upload_logo(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $file = UploadedFile::fake()->image('logo.png');

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.organizations.update-image', $organization), [
            'logo' => $file,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_validates_image_file_required(): void
    {
        // Arrange
        Storage::fake('public');
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.update-image', $organization), []);

        // Assert
        $response->assertSessionHasErrors(['logo']);
    }

    public function test_invoke_validates_image_file_type(): void
    {
        // Arrange
        Storage::fake('public');
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf');

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.update-image', $organization), [
            'logo' => $file,
        ]);

        // Assert
        $response->assertSessionHasErrors(['logo']);
    }
}
