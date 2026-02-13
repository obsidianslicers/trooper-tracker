<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Updated Event',
            Event::EVENT_START => now()->addDays(30)->toDateTimeString(),
            Event::EVENT_END => now()->addDays(30)->addHours(2)->toDateTimeString(),
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_updates_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::NAME => 'Original Name']);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Updated Event Name',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect(route('admin.events.update', $event));
        $this->assertDatabaseHas(Event::class, [
            Event::ID => $event->id,
            Event::NAME => 'Updated Event Name',
        ]);
    }

    public function test_invoke_redirects_to_update_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect(route('admin.events.update', $event));
    }

    public function test_invoke_administrator_can_update_any_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
    }

    public function test_invoke_moderator_can_update_moderated_event(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create([Event::ORGANIZATION_ID => $org->id]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Moderator Updated',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Event::class, [
            Event::ID => $event->id,
            Event::NAME => 'Moderator Updated',
        ]);
    }

    public function test_invoke_moderator_cannot_update_non_moderated_event(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create([Event::ORGANIZATION_ID => $other_org->id]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Should Not Update',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_event(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Should Not Update',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertForbidden();
    }
}
