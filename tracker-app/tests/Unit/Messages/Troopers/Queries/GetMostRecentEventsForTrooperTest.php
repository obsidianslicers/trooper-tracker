<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetMostRecentEventsForTrooper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMostRecentEventsForTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sets_most_recent_attended_shift_per_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $org_a = Organization::factory()->asOrganization()->withNodePath('100.')->withName('Alpha')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200.')->withName('Bravo')->create();

        $event_old = Event::factory()->withOrganization($org_a)->create();
        $event_new = Event::factory()->withOrganization($org_a)->create();

        $old_shift = EventShift::factory()->forEvent($event_old)->withShiftStartsAt(Carbon::parse('2026-01-01 10:00:00'))->create();
        $new_shift = EventShift::factory()->forEvent($event_new)->withShiftStartsAt(Carbon::parse('2026-02-01 10:00:00'))->create();

        EventTrooper::factory()->forEventShift($old_shift)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($new_shift)->forTrooper($trooper)->asAttended()->create();

        $result = (new GetMostRecentEventsForTrooper($trooper))->handle();

        $alpha = $result->firstWhere('id', $org_a->id);
        $bravo = $result->firstWhere('id', $org_b->id);

        $this->assertNotNull($alpha);
        $this->assertNotNull($alpha->event_shift);
        $this->assertSame($new_shift->id, $alpha->event_shift->id);
        $this->assertNull($bravo->event_shift);
    }
}
