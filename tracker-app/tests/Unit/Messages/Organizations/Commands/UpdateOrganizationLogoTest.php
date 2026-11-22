<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Commands;

use App\Messages\Organizations\Commands\UpdateOrganizationLogo;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateOrganizationLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_stores_square_large_and_small_logos(): void
    {
        Storage::fake('public');
        $organization = Organization::factory()->create();

        $subject = new UpdateOrganizationLogo($organization, UploadedFile::fake()->image('logo.png', 300, 150));
        $subject->handle();

        $large = "uploads/organizations/{$organization->id}-128x128.png";
        $small = "uploads/organizations/{$organization->id}-32x32.png";
        Storage::disk('public')->assertExists([$large, $small]);
        $this->assertSame([128, 128], array_slice(getimagesizefromstring(Storage::disk('public')->get($large)), 0, 2));
        $this->assertSame([32, 32], array_slice(getimagesizefromstring(Storage::disk('public')->get($small)), 0, 2));

        $organization->refresh();
        $this->assertSame($large, $organization->image_path_lg);
        $this->assertSame($small, $organization->image_path_sm);
    }
}
