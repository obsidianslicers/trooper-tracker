<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\UploadEventImages;
use App\Models\Event;
use App\Models\Trooper;
use App\Services\EventImageUploadResult;
use App\Services\EventImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UploadEventImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_uses_the_events_default_when_no_type_is_given(): void
    {
        $event = Event::factory()->withEventStart(now()->addWeek())->create();
        $trooper = Trooper::factory()->asActive()->create();
        $result = new EventImageUploadResult(1, 0);

        $subject = new UploadEventImages($event, $trooper, []);

        $this->assertSame($result, $subject->handle($this->expectUpload($event, $trooper, true, $result)));
    }

    public function test_handle_uses_the_type_given(): void
    {
        $event = Event::factory()->withEventStart(now()->addWeek())->create();
        $trooper = Trooper::factory()->asActive()->create();
        $result = new EventImageUploadResult(1, 0);

        $subject = new UploadEventImages($event, $trooper, [], is_instructional: false);

        $this->assertSame($result, $subject->handle($this->expectUpload($event, $trooper, false, $result)));
    }

    private function expectUpload(
        Event $event,
        Trooper $trooper,
        bool $is_administrative,
        EventImageUploadResult $result,
    ): EventImageUploadService {
        $uploads = Mockery::mock(EventImageUploadService::class);
        $uploads->shouldReceive('upload')
            ->once()
            ->with($event, $trooper->id, [], $is_administrative)
            ->andReturn($result);

        return $uploads;
    }
}
