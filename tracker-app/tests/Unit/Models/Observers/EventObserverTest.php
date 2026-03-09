<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Models\Event;
use App\Models\Observers\EventObserver;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_sets_primary_organization_to_top_level_club(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();

        $subject = new EventObserver();
        $event = Event::factory()->withOrganization($region)->make();

        $subject->creating($event);

        $this->assertSame($club->id, $event->primary_organization_id);
    }
}