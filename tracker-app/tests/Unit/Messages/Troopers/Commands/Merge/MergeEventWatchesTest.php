<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeEventWatches;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventWatchesTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_event_watches_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $event_one = Event::factory()->create();
        $event_two = Event::factory()->create();

        $watch_one = EventWatch::factory()
            ->forEvent($event_one)
            ->forTrooper($source_trooper)
            ->create();

        $watch_two = EventWatch::factory()
            ->forEvent($event_two)
            ->forTrooper($source_trooper)
            ->create();

        MergeEventWatches::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_watches', [
            EventWatch::ID => $watch_one->id,
            EventWatch::EVENT_ID => $event_one->id,
            EventWatch::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseHas('tt_event_watches', [
            EventWatch::ID => $watch_two->id,
            EventWatch::EVENT_ID => $event_two->id,
            EventWatch::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_watches', [
            EventWatch::ID => $watch_one->id,
            EventWatch::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_watches', [
            EventWatch::ID => $watch_two->id,
            EventWatch::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_deletes_source_watch_when_target_already_watches_same_event(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $event = Event::factory()->create();

        $target_watch = EventWatch::factory()
            ->forEvent($event)
            ->forTrooper($target_trooper)
            ->create();

        $source_watch = EventWatch::factory()
            ->forEvent($event)
            ->forTrooper($source_trooper)
            ->create();

        MergeEventWatches::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_watches', [
            EventWatch::ID => $target_watch->id,
            EventWatch::EVENT_ID => $event->id,
            EventWatch::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_watches', [
            EventWatch::ID => $source_watch->id,
        ]);

        $this->assertSame(
            1,
            EventWatch::query()
                ->where(EventWatch::EVENT_ID, $event->id)
                ->where(EventWatch::TROOPER_ID, $target_trooper->id)
                ->count(),
        );
    }
}
