<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventAdminQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_stores_event(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $query = new GetTroopersForEventAdminQuery($event);

        // Assert
        $this->assertSame($event, $query->event);
    }

    public function test_query_is_readonly(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act & Assert
        $this->expectException(\Error::class);
        $query->event = Event::factory()->create();
    }
}
