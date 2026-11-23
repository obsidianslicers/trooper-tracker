<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\SignUpChangeOutcome;
use App\Messages\Events\Commands\ChangeEventTrooperOrganization;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeEventTrooperOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_changes_the_organization_and_clears_costumes(): void
    {
        $event_trooper = $this->signUp();
        $organization = Organization::factory()->create();

        $subject = new ChangeEventTrooperOrganization($event_trooper, $organization->id, $event_trooper->trooper);
        $result = $subject->handle();

        $event_trooper->refresh();
        $this->assertSame(SignUpChangeOutcome::SAVED, $result);
        $this->assertSame($organization->id, $event_trooper->organization_id);
        $this->assertNull($event_trooper->costume_id);
        $this->assertNull($event_trooper->backup_costume_id);
    }

    public function test_handle_forbids_someone_else(): void
    {
        $event_trooper = $this->signUp();
        $stranger = Trooper::factory()->asActive()->create();

        $subject = new ChangeEventTrooperOrganization($event_trooper, null, $stranger);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::FORBIDDEN, $result);
        $this->assertNotNull($event_trooper->fresh()->costume_id);
    }

    private function signUp(): EventTrooper
    {
        $shift = EventShift::factory()->forEvent(Event::factory()->create())->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->withCostume(Costume::factory()->create())
            ->withBackupCostume(Costume::factory()->create())
            ->asTentative()
            ->create();
    }
}
