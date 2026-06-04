<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_upload_stores_administrative_image_and_returns_flash_header(): void
    {
        Storage::fake('public');

        $admin = Trooper::factory()->asAdministrator()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('admin-upload.jpg', 800, 600)->size(2048);

        $response = $this->actingAs($admin)
            ->withHeader('HX-Request', 'true')
            ->post(route('admin.events.upload-image', $event), [
                'images' => [$file],
            ]);

        $response->assertOk();
        $response->assertHeader('X-Flash-Message');
        $response->assertSee('event-uploads', false);

        $upload = EventUpload::query()->firstOrFail();

        $this->assertTrue($upload->is_administrative);
        $this->assertSame($event->id, $upload->event_id);
        $this->assertSame($admin->id, $upload->trooper_id);
        Storage::disk('public')->assertExists($upload->image_path_lg);
        Storage::disk('public')->assertExists($upload->image_path_sm);
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
