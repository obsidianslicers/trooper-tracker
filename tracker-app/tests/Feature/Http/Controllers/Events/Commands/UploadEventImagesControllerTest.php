<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use App\Services\EventImageUploadResult;
use App\Services\EventImageUploadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\ReadsToasts;
use Tests\TestCase;

class UploadEventImagesControllerTest extends TestCase
{
    use ReadsToasts;
    use RefreshDatabase;

    public function test_mission_review_upload_stores_image_and_says_so(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->withEventStart(Carbon::now()->subDay())->create();
        $file = UploadedFile::fake()->image('mission-review.jpg', 800, 600)->size(2048);

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => [$file],
            ]);

        $response->assertRedirect(route('events.display', ['event' => $event]));
        $this->assertSame('success', $this->toastType());

        $upload = EventUpload::query()->firstOrFail();

        $this->assertFalse($upload->is_administrative);
        $this->assertSame($event->id, $upload->event_id);
        $this->assertSame($trooper->id, $upload->trooper_id);
        Storage::disk('public')->assertExists($upload->image_path_lg);
        Storage::disk('public')->assertExists($upload->image_path_sm);
    }

    public function test_upload_before_the_event_defaults_to_instructional(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->withEventStart(Carbon::now()->addWeek())->create();

        $response = $this->postImage($trooper, $event, []);

        $response->assertRedirect(route('events.display', ['event' => $event]));
        $this->assertTrue(EventUpload::query()->firstOrFail()->is_administrative);
    }

    public function test_upload_as_troop_photo_before_the_event_goes_to_mission_review(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->withEventStart(Carbon::now()->addWeek())->create();

        $response = $this->postImage($trooper, $event, ['upload_type' => 'troop']);

        $response->assertRedirect();
        $this->assertFalse(EventUpload::query()->firstOrFail()->is_administrative);
    }

    public function test_upload_as_instructional_after_the_event_starts_is_instructional(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->withEventStart(Carbon::now()->subDay())->create();

        $response = $this->postImage($trooper, $event, ['upload_type' => 'instructional']);

        $response->assertRedirect();
        $this->assertTrue(EventUpload::query()->firstOrFail()->is_administrative);
    }

    public function test_oversized_image_says_why(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('too-large.jpg')->size(51201);

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => [$file],
            ]);

        $response->assertRedirect();
        $this->assertSame('danger', $this->toastType());
        $this->assertStringContainsString('50MB', (string) $this->toast());
        $this->assertSame(0, EventUpload::query()->count());
    }

    public function test_post_too_large_request_returns_visible_htmx_error(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->withServerVariables([
                'CONTENT_LENGTH' => (string) (13 * 1024 * 1024),
            ])
            ->post(route('events.upload-image', $event));

        $response->assertStatus(413);
        $response->assertHeader('X-Flash-Message');
        $this->assertStringContainsString('too large for the server', (string) $response->headers->get('X-Flash-Message'));
        $this->assertSame(0, EventUpload::query()->count());
    }

    public function test_php_level_upload_failure_returns_friendly_error(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        // Simulates a file PHP rejected for exceeding upload_max_filesize before
        // Laravel's own validation rules ever run against its content.
        $file = new UploadedFile(
            UploadedFile::fake()->image('too-big-for-php.jpg')->getPathname(),
            'too-big-for-php.jpg',
            'image/jpeg',
            UPLOAD_ERR_INI_SIZE,
            true
        );

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => [$file],
            ]);

        $response->assertRedirect();
        $this->assertSame('danger', $this->toastType());
        $this->assertStringContainsString('failed to upload', (string) $this->toast());
        $this->assertStringNotContainsString('images.0', (string) $this->toast());
        $this->assertSame(0, EventUpload::query()->count());
    }

    public function test_heic_extension_passes_validation(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->create('phone-photo.heic', 2048, 'image/heic');

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => [$file],
            ]);

        // The fake file's bytes are not a real HEIC image, so conversion fails
        // gracefully after passing validation rather than being rejected as an
        // unsupported format up front.
        $response->assertRedirect();
        $this->assertSame('danger', $this->toastType());
        $this->assertStringContainsString('No images were uploaded', (string) $this->toast());
        $this->assertSame(0, EventUpload::query()->count());
    }

    public function test_partial_processing_failure_returns_warning_flash(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $files = [
            UploadedFile::fake()->image('good.jpg')->size(1024),
            UploadedFile::fake()->image('also-valid.png')->size(1024),
        ];

        $this->mock(EventImageUploadService::class, function ($mock): void
        {
            $mock->shouldReceive('upload')
                ->once()
                ->andReturn(new EventImageUploadResult(1, 1));
        });

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => $files,
            ]);

        $response->assertRedirect();
        $this->assertNotNull($this->toast());
        $flash = (string) $this->toast();
        $this->assertStringContainsString('1 image uploaded', $flash);
        $this->assertStringContainsString('1 image failed', $flash);
        $this->assertSame('warning', $this->toastType());
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('troop.png');

        $response = $this->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', ['event' => $event->id]), [
                'images' => [$file],
            ]);

        $response->assertRedirect(route('auth.login'));
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function postImage(Trooper $trooper, Event $event, array $fields): TestResponse
    {
        return $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), $fields + [
                'images' => [UploadedFile::fake()->image('map.png', 400, 300)],
            ]);
    }
}
