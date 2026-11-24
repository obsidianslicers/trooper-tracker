<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\Resources\Record;

use App\Enums\EventTrooperStatus;
use App\Messages\ServiceRecords\Resources\Record\ServiceRecordShiftResource;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ServiceRecordShiftResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_shift_shows_command_staff_costume_for_handler_pool_assignment(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();
        $shift = $this->attendedShift($trooper, Event::factory()->create(), [
            EventTrooper::COSTUME_ID => $costume->id,
            EventTrooper::IS_HANDLER => true,
        ]);

        $subject = (new ServiceRecordShiftResource($shift, ServiceRecordShiftResource::RECENT))->resolve();

        $this->assertSame(Costume::COMMAND_STAFF, $subject['costume']);
        $this->assertNull($subject['not_counted']);
        $this->assertNull($subject['same_day_repeat']);
    }

    public function test_recent_shift_marks_a_troop_that_does_not_count(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $uncounted = EventType::factory()->uncounted()->create([EventType::NAME => 'Armor Party Plus']);
        $event = Event::factory()->create([Event::EVENT_TYPE_ID => $uncounted->id]);
        $shift = $this->attendedShift($trooper, $event);

        $subject = (new ServiceRecordShiftResource($shift, ServiceRecordShiftResource::RECENT))->resolve();

        $this->assertSame([
            'label' => 'Non-troop-credit',
            'hint' => 'Non-troop-credit event: Does not count toward troop totals.',
        ], $subject['not_counted']);
    }

    public function test_recent_shift_marks_a_second_shift_on_the_same_day(): void
    {
        config(['tracker.count_as_one_troop_per_day' => true]);
        $trooper = Trooper::factory()->asMember()->create();
        $club = Organization::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $day = Carbon::now()->subDays(3)->setTime(9, 0);

        foreach ([$day, $day->copy()->setTime(13, 0)] as $start)
        {
            $shift = EventShift::factory()
                ->forEvent($event)
                ->asClosed()
                ->withShiftStartsAt($start)
                ->withShiftEndsAt($start->copy()->addHours(3))
                ->create();
            EventTrooper::factory()
                ->forEventShift($shift)
                ->forTrooper($trooper)
                ->asAttended()
                ->create([EventTrooper::COSTUME_ORGANIZATION_IDS => [$club->id]]);
        }

        $record = GetTrooperServiceRecord::call(trooper_id: $trooper->id);
        $rows = $record['recent_shifts']
            ->map(fn (EventShift $shift): array => (new ServiceRecordShiftResource($shift, ServiceRecordShiftResource::RECENT))->resolve())
            ->values();

        $this->assertSame(1, $record['club_troop_counts']->firstWhere('id', $club->id)?->troop_count);
        $this->assertSame(1, $rows->whereNotNull('same_day_repeat')->count());
        $this->assertNull($rows->last()['same_day_repeat']);
        $this->assertSame('Same day, counted once', $rows->first()['same_day_repeat']['label']);
        $this->assertStringContainsString('already counted as the troop for the day', $rows->first()['same_day_repeat']['hint']);
    }

    public function test_pending_shift_links_made_it_and_missed_it(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $shift = $this->attendedShift($trooper, Event::factory()->create([Event::NAME => 'Parade']));

        $subject = (new ServiceRecordShiftResource($shift, ServiceRecordShiftResource::PENDING))->resolve();

        $this->assertSame('Parade', $subject['event_name']);
        $this->assertSame(EventTrooperStatus::ATTENDED->value, $this->statusIn($subject['made_it_url']));
        $this->assertSame(EventTrooperStatus::UNABLE_TO_ATTEND->value, $this->statusIn($subject['missed_it_url']));
        $this->assertArrayNotHasKey('costume', $subject);
    }

    private function statusIn(string $url): string
    {
        return Crypt::decryptString(basename($url));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function attendedShift(Trooper $trooper, Event $event, array $state = []): EventShift
    {
        $shift = EventShift::factory()->forEvent($event)->asClosed()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->state($state)
            ->create();

        $shift->setRelation('event', $event->load('event_type', 'organization'));
        $shift->event_trooper = $event_trooper->load('costume');

        return $shift;
    }
}
