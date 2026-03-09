<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_uploads_event_image_for_admin_successfully(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('admin-upload.png');

        $response = $this->actingAs($trooper)->post(route('admin.events.upload-image', ['event' => $event->id]), [
            'images' => [$file],
        ]);

        $response->assertOk();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('admin-upload.png');

        $response = $this->post(route('admin.events.upload-image', ['event' => $event->id]), [
            'images' => [$file],
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
