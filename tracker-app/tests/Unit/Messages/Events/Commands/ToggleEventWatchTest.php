<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventWatchOutcome;
use App\Events\EventWatchChanging;
use App\Messages\Events\Commands\ToggleEventWatch;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;

class ToggleEventWatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_starts_watching(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $subject = new ToggleEventWatch($event, $trooper);
        $result = $subject->handle();

        $this->assertSame(EventWatchOutcome::WATCHING, $result);
        $this->assertTrue($this->isWatching($event, $trooper));
    }

    public function test_handle_stops_watching(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        EventWatch::create(['event_id' => $event->id, 'trooper_id' => $trooper->id]);

        $subject = new ToggleEventWatch($event, $trooper);
        $result = $subject->handle();

        $this->assertSame(EventWatchOutcome::NOT_WATCHING, $result);
        $this->assertFalse($this->isWatching($event, $trooper));
    }

    public function test_handle_leaves_it_when_a_listener_refuses(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        EventFacade::listen(EventWatchChanging::class, fn (): bool => false);

        $subject = new ToggleEventWatch($event, $trooper);
        $result = $subject->handle();

        $this->assertSame(EventWatchOutcome::UNCHANGED, $result);
        $this->assertFalse($this->isWatching($event, $trooper));
    }

    private function isWatching(Event $event, Trooper $trooper): bool
    {
        return EventWatch::where('event_id', $event->id)->where('trooper_id', $trooper->id)->exists();
    }
}
