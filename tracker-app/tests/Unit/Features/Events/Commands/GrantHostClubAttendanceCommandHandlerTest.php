<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\GrantHostClubAttendanceCommand;
use App\Features\Events\Commands\GrantHostClubAttendanceCommandHandler;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see GrantHostClubAttendanceCommandHandler
 */
class GrantHostClubAttendanceCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_attendance_row_for_top_level_host_club(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $event = Event::factory()->withOrganization($region)->create();

        $subject = new GrantHostClubAttendanceCommandHandler;
        $subject(new GrantHostClubAttendanceCommand($event));

        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $club->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
    }

    public function test_invoke_re_enables_existing_attendance_row(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $event = Event::factory()->withOrganization($club)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $club->id,
            EventOrganization::CAN_ATTEND => false,
        ]);

        $subject = new GrantHostClubAttendanceCommandHandler;
        $subject(new GrantHostClubAttendanceCommand($event));

        $this->assertSame(
            1,
            EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
                ->where(EventOrganization::ORGANIZATION_ID, $club->id)
                ->count()
        );
        $this->assertTrue(
            (bool) EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
                ->where(EventOrganization::ORGANIZATION_ID, $club->id)
                ->value(EventOrganization::CAN_ATTEND)
        );
    }
}
