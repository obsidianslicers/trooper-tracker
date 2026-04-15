<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleEventUploadTagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_toggles_tag_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        /** @var EventUpload $upload */
        $upload = EventUpload::factory()->for($event)->create();

        // Initially, trooper is not tagged
        $this->assertFalse($upload->troopers()->where('tt_troopers.id', $trooper->id)->exists());

        // First call: should tag the trooper
        $response = $this->actingAs($trooper)->post(route('events.toggle-upload-tag', [
            'event_upload' => $upload->id,
        ]));

        $response->assertOk();
        $this->assertTrue($upload->fresh()->troopers()->where('tt_troopers.id', $trooper->id)->exists());

        // Second call: should untag the trooper
        $response = $this->actingAs($trooper)->post(route('events.toggle-upload-tag', [
            'event_upload' => $upload->id,
        ]));

        $response->assertOk();
        $this->assertFalse($upload->fresh()->troopers()->where('tt_troopers.id', $trooper->id)->exists());
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();

        $response = $this->post(route('events.toggle-upload-tag', [
            'event_upload' => $upload->id,
        ]));

        $response->assertRedirect(route('auth.login'));
    }
}
