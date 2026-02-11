<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\GoogleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ShiftCompleteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock GoogleService to prevent API calls during tests
        $this->mock(GoogleService::class, function ($mock)
        {
            $mock->shouldReceive('getLatitudeLongitude')
                ->andReturn([0.0, 0.0]);
        });
    }

    public function test_invoke_requires_valid_encrypted_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create();
        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper,
            'status' => $encrypted_status,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_displays_status_update_confirmation(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create();
        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper,
            'status' => $encrypted_status,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');
    }

    public function test_invoke_passes_event_trooper_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create();
        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper,
            'status' => $encrypted_status,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('event_trooper');
    }

    public function test_invoke_updates_event_trooper_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([
            Event::EVENT_END => now()->addDays(2),
        ]);
        $event_shift = EventShift::factory()->withEvent($event)->create();
        $event_trooper = EventTrooper::factory()->withShift($event_shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper,
            'status' => $encrypted_status,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('message');
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED->value,
        ]);
    }

    public function test_invoke_does_not_update_status_when_event_updates_are_locked(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::STATUS => \App\Enums\EventStatus::CLOSED,
            Event::EVENT_START => Carbon::now()->subDays(40),
            Event::EVENT_END => Carbon::now()->subDays(31),
        ]);
        $event_shift = EventShift::factory()->withEvent($event)->create();
        $event_trooper = EventTrooper::factory()->withShift($event_shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper,
            'status' => $encrypted_status,
        ]));

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);

        // Cleanup
        Carbon::setTestNow();
    }

    public function test_invoke_rejects_invalid_encrypted_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper,
            'status' => 'invalid-encrypted-data',
        ]));

        // Assert
        $response->assertNotFound();
    }
}
