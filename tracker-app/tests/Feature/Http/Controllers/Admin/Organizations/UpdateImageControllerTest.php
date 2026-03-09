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

    public function test_invoke_uploads_organization_logo_and_renders_partial(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($trooper)->post(route('admin.organizations.update-image', ['organization' => $organization->id]), [
            'logo' => $logo,
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.admin.organizations.image');
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
