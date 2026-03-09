<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Features\Events\Queries\GetTroopersForEventAdminQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventAdminQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_shifts_sorted_by_start_time_and_enriched_troopers(): void
    {
        $event = Event::factory()->create();
        $later_shift = EventShift::factory()->forEvent($event)->withShiftStartsAt(Carbon::parse('2026-03-10 13:00:00'))->create();
        $earlier_shift = EventShift::factory()->forEvent($event)->withShiftStartsAt(Carbon::parse('2026-03-10 09:00:00'))->create();

        $trooper = Trooper::factory()->asMember()->create();

        EventTrooper::factory()->forEventShift($earlier_shift)->forTrooper($trooper)->create();
        EventTrooper::factory()->forEventShift($later_shift)->forTrooper($trooper)->create();

        $subject = new GetTroopersForEventAdminQueryHandler();

        $result = $subject(new GetTroopersForEventAdminQuery($event));

        $this->assertSame($earlier_shift->id, $result->first()->id);
        $this->assertTrue(isset($result->first()->event_troopers->first()->costume_organizations));
    }
}
