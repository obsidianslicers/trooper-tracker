<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\ToggleEventUploadTag;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleEventUploadTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_tags_an_untagged_trooper(): void
    {
        $event_upload = EventUpload::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $subject = new ToggleEventUploadTag($event_upload, $trooper);
        $result = $subject->handle();

        $this->assertTrue($result);
        $this->assertTrue($event_upload->troopers()->where('tt_troopers.id', $trooper->id)->exists());
    }

    public function test_handle_untags_a_tagged_trooper(): void
    {
        $event_upload = EventUpload::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_upload->troopers()->attach($trooper->id);

        $subject = new ToggleEventUploadTag($event_upload, $trooper);
        $result = $subject->handle();

        $this->assertFalse($result);
        $this->assertFalse($event_upload->troopers()->where('tt_troopers.id', $trooper->id)->exists());
    }
}
