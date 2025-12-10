<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\EventStatus;
use App\Models\OrganizationCostume;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventScopesTest extends TestCase
{
    use RefreshDatabase;

    // public function test_by_trooper_scope_for_open_events(): void
    // {
    //     // Arrange
    //     $trooper = Trooper::factory()->create();
    //     $costume = Costume::factory()->create();

    //     $open_event = Event::factory()->withAssignment($trooper, $costume)->open()->create();
    //     $closed_event = Event::factory()->withAssignment($trooper, $costume)->closed()->create();

    //     Event::factory()->closed()->create();

    //     // Act
    //     $result = Event::byTrooper($trooper->id, false)->get();

    //     // Assert
    //     $this->assertCount(1, $result);
    //     $this->assertEquals($open_event->id, $result->first()->id);
    //     $this->assertEquals($result->first()->status, EventStatus::OPEN);
    // }

    // public function test_by_trooper_scope_for_closed_events(): void
    // {
    //     // Arrange
    //     $trooper = Trooper::factory()->create();
    //     $costume = Costume::factory()->create();

    //     $open_event = Event::factory()->withAssignment($trooper, $costume)->open()->create();
    //     $closed_event = Event::factory()->withAssignment($trooper, $costume)->closed()->create();

    //     Event::factory()->closed()->create(); // Another closed event, not for our trooper

    //     // Act
    //     $result = Event::byTrooper($trooper->id, true)->get();

    //     // Assert
    //     $this->assertCount(1, $result);
    //     $this->assertEquals($closed_event->id, $result->first()->id);
    //     $this->assertEquals($result->first()->status, EventStatus::CLOSED);
    // }

    public function test_moderated_by_scope(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;
        $unrelated_unit = Organization::factory()->unit()->create();

        $moderator = Trooper::factory()->asModerator()->withAssignment($region, moderator: true)->create();

        // Events
        $event_in_moderated_org = Event::factory()->withOrganization($region)->create();
        $event_in_child_org = Event::factory()->withOrganization($unit)->create();
        $event_in_unrelated_org = Event::factory()->withOrganization($organization)->create();

        // Act
        $result = Event::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($event_in_moderated_org));
        $this->assertTrue($result->contains($event_in_child_org));
        $this->assertFalse($result->contains($event_in_unrelated_org));
    }

    public function test_moderated_by_scope_with_no_moderated_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        Event::factory()->create(['organization_id' => $organization->id]);

        // Act
        $result = Event::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_search_for_scope(): void
    {
        // Arrange
        $matching_event = Event::factory()->create([
            'name' => 'Match Doe',
        ]);

        Event::factory()->create([
            'name' => 'NoMatch',
        ]);

        // Act
        $result = Event::searchFor('atch D')->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($matching_event->id, $result->first()->id);
    }
}