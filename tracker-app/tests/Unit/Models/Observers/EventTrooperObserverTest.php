<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Observers\EventTrooperObserver;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTrooperObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_assigns_costume_organization_ids_when_costume_changes(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $costume->id;

        $subject = new EventTrooperObserver();

        $subject->saving($event_trooper);

        $this->assertSame([
            $organization->id,
        ], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
        $this->assertSame([], $event_trooper->{EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS});
    }
}