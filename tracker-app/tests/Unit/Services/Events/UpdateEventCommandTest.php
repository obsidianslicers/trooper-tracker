<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\Events\UpdateEventCommand;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for UpdateEventCommand.
 *
 * Verifies:
 * - Updates event's core properties including name, status, and attendance limits.
 * - Updates location coordinates (latitude/longitude).
 * - Updates contact information.
 * - Updates venue details.
 * - Updates event timing (start/end dates).
 * - Updates request specifics and amenities.
 * - Persists all changes to database.
 * - Only updates fields present in data array (nullable fields).
 */
class UpdateEventCommandTest extends TestCase
{
    use RefreshDatabase;

    private UpdateEventCommand $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateEventCommand();
    }

    /**
     * Helper to build complete event data array with all required fields.
     *
     * @param array $overrides Optional overrides for specific fields
     * @return array
     */
    private function buildEventData(array $overrides = []): array
    {
        $defaults = [
            'name' => 'Test Event',
            'status' => EventStatus::OPEN->value,
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
            'event_start' => now(),
            'event_end' => now()->addHours(2),
        ];

        return array_merge($defaults, $overrides);
    }

    public function test_invoke_updates_name_and_status(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::NAME => 'Old Event Name',
            Event::STATUS => EventStatus::DRAFT,
        ]);

        $data = $this->buildEventData([
            'name' => 'New Event Name',
            'status' => EventStatus::OPEN->value,
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals('New Event Name', $event->name);
        $this->assertEquals(EventStatus::OPEN, $event->status);
    }

    public function test_invoke_updates_attendance_limits(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => null,
            Event::HANDLERS_ALLOWED => null,
            Event::FRIENDS_ALLOWED => null,
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'troopers_allowed' => 20,
            'handlers_allowed' => 5,
            'friends_allowed' => 10,
            'tentative_signups_allowed' => true,
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals(20, $event->troopers_allowed);
        $this->assertEquals(5, $event->handlers_allowed);
        $this->assertEquals(10, $event->friends_allowed);
        $this->assertTrue($event->tentative_signups_allowed);
    }

    public function test_invoke_updates_location_coordinates(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::LATITUDE => null,
            Event::LONGITUDE => null,
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals(40.7128, $event->latitude);
        $this->assertEquals(-74.0060, $event->longitude);
    }

    public function test_invoke_updates_contact_information(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::CONTACT_NAME => null,
            Event::CONTACT_PHONE => null,
            Event::CONTACT_EMAIL => null,
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'contact_name' => 'John Doe',
            'contact_phone' => '555-1234',
            'contact_email' => 'john@example.com',
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals('John Doe', $event->contact_name);
        $this->assertEquals('555-1234', $event->contact_phone);
        $this->assertEquals('john@example.com', $event->contact_email);
    }

    public function test_invoke_updates_venue_details(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::VENUE => null,
            Event::VENUE_ADDRESS => null,
            Event::VENUE_CITY => null,
            Event::VENUE_STATE => null,
            Event::VENUE_ZIP => null,
            Event::VENUE_COUNTRY => null,
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'venue' => 'Convention Center',
            'venue_address' => '123 Main St',
            'venue_city' => 'Springfield',
            'venue_state' => 'IL',
            'venue_zip' => '62701',
            'venue_country' => 'USA',
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals('Convention Center', $event->venue);
        $this->assertEquals('123 Main St', $event->venue_address);
        $this->assertEquals('Springfield', $event->venue_city);
        $this->assertEquals('IL', $event->venue_state);
        $this->assertEquals('62701', $event->venue_zip);
        $this->assertEquals('USA', $event->venue_country);
    }

    public function test_invoke_updates_event_timing(): void
    {
        // Arrange
        $event = Event::factory()->create();

        $start_time = Carbon::parse('2026-06-15 10:00:00');
        $end_time = Carbon::parse('2026-06-15 18:00:00');

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'event_start' => $start_time,
            'event_end' => $end_time,
            'event_website' => 'https://example.com/event',
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertTrue($start_time->eq($event->event_start));
        $this->assertTrue($end_time->eq($event->event_end));
        $this->assertEquals('https://example.com/event', $event->event_website);
    }

    public function test_invoke_updates_request_specifics(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::EXPECTED_ATTENDEES => null,
            Event::REQUESTED_NUMBER_CHARACTERS => null,
            Event::REQUESTED_CHARACTER_TYPES => null,
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'expected_attendees' => 500,
            'requested_number_characters' => 15,
            'requested_character_types' => 'Stormtroopers and Rebels',
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals(500, $event->expected_attendees);
        $this->assertEquals(15, $event->requested_number_characters);
        $this->assertEquals('Stormtroopers and Rebels', $event->requested_character_types);
    }

    public function test_invoke_updates_venue_amenities(): void
    {
        // Arrange
        $event = Event::factory()->create();

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'secure_staging_area' => true,
            'allow_blasters' => true,
            'allow_props' => true,
            'parking_available' => true,
            'accessible' => true,
            'amenities' => 'Restrooms, Food, Water',
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertTrue($event->secure_staging_area);
        $this->assertTrue($event->allow_blasters);
        $this->assertTrue($event->allow_props);
        $this->assertTrue($event->parking_available);
        $this->assertTrue($event->accessible);
        $this->assertEquals('Restrooms, Food, Water', $event->amenities);
    }

    public function test_invoke_updates_charity_information(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::CHARITY_NAME => null,
            Event::CHARITY_HOURS => null,
            Event::CHARITY_DIRECT_FUNDS => 0,
            Event::CHARITY_INDIRECT_FUNDS => 0,
            Event::CHARITY_NOTES => null,
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'charity_name' => 'Make-A-Wish Foundation',
            'charity_hours' => 150,
            'charity_direct_funds' => 5000,
            'charity_indirect_funds' => 2500,
            'charity_notes' => 'Funds raised through silent auction',
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals('Make-A-Wish Foundation', $event->charity_name);
        $this->assertEquals(150, $event->charity_hours);
        $this->assertEquals(5000, $event->charity_direct_funds);
        $this->assertEquals(2500, $event->charity_indirect_funds);
        $this->assertEquals('Funds raised through silent auction', $event->charity_notes);
    }

    public function test_invoke_defaults_charity_funds_to_zero_when_not_provided(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::CHARITY_DIRECT_FUNDS => 1000,
            Event::CHARITY_INDIRECT_FUNDS => 500,
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals(0, $event->charity_direct_funds);
        $this->assertEquals(0, $event->charity_indirect_funds);
    }

    public function test_invoke_updates_miscellaneous_fields(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::COMMENTS => null,
            Event::REFERRED_BY => null,
            Event::SOURCE => 'web',
        ]);

        $data = $this->buildEventData([
            'name' => $event->name,
            'status' => $event->status->value,
            'comments' => 'Special instructions for troopers',
            'referred_by' => 'Jane Smith',
            'source' => 'email',
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals('Special instructions for troopers', $event->comments);
        $this->assertEquals('Jane Smith', $event->referred_by);
        $this->assertEquals('email', $event->source);
    }

    public function test_invoke_uses_existing_source_when_not_provided(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::SOURCE => 'original_source',
        ]);

        $data = $this->buildEventData([
            'name' => 'Updated Name',
            'status' => $event->status->value,
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals('original_source', $event->source);
    }

    public function test_invoke_clears_nullable_fields_when_not_provided(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::LATITUDE => 40.7128,
            Event::LONGITUDE => -74.0060,
            Event::CONTACT_NAME => 'Old Contact',
            Event::VENUE => 'Old Venue',
        ]);

        $data = $this->buildEventData([
            'name' => 'Updated Event',
            'status' => $event->status->value,
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertNull($event->latitude);
        $this->assertNull($event->longitude);
        $this->assertNull($event->contact_name);
        $this->assertNull($event->venue);
    }

    public function test_invoke_persists_changes_to_database(): void
    {
        // Arrange
        $event = Event::factory()->create([
            Event::NAME => 'Original Name',
        ]);

        $data = $this->buildEventData([
            'name' => 'Database Test Event',
            'status' => EventStatus::OPEN->value,
        ]);

        // Act
        ($this->subject)($event, $data);

        // Assert
        $this->assertDatabaseHas('tt_events', [
            'id' => $event->id,
            'name' => 'Database Test Event',
            'status' => EventStatus::OPEN->value,
        ]);
    }
}
