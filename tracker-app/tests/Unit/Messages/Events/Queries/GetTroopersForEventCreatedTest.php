<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Enums\NotificationFrequency;
use App\Messages\Events\Queries\GetTroopersForEventCreated;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventCreatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_active_troopers_with_notification_opt_in_for_event_organization(): void
    {
        $organization = Organization::factory()->create();
        $other_organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();

        $eligible = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::INSTANT)->create();
        TrooperAssignment::factory()->forTrooper($eligible)->forOrganization($organization)->withShouldNotify()->create();

        $never_notify = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::NEVER)->create();
        TrooperAssignment::factory()->forTrooper($never_notify)->forOrganization($organization)->withShouldNotify()->create();

        $no_opt_in = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::INSTANT)->create();
        TrooperAssignment::factory()->forTrooper($no_opt_in)->forOrganization($organization)->create();

        $other_org_trooper = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::INSTANT)->create();
        TrooperAssignment::factory()->forTrooper($other_org_trooper)->forOrganization($other_organization)->withShouldNotify()->create();

        $result = (new GetTroopersForEventCreated($event))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($eligible->id, $result->first()->id);
    }
}
