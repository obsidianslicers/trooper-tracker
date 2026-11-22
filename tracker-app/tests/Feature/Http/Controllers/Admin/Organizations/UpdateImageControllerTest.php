<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_uploads_organization_logo_and_returns_to_the_form(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($trooper)->post(route('admin.organizations.update-image', ['organization' => $organization->id]), [
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('admin.organizations.update', ['organization' => $organization->id]));
        Storage::disk('public')->assertExists("uploads/organizations/{$organization->id}-128x128.png");
        Storage::disk('public')->assertExists("uploads/organizations/{$organization->id}-32x32.png");
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->post(route('admin.organizations.update-image', ['organization' => $organization->id]), [
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
