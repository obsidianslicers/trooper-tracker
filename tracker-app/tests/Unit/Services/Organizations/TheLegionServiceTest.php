<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Organizations;

use App\Models\Event;
use App\Services\Organizations\TheLegionService;
use Carbon\Carbon;
use Tests\TestCase;

class TheLegionServiceTest extends TestCase
{
    public function test_parse_request_appearance_with_full_data(): void
    {
        $message = <<<EOT
Contact Name: John Doe
Contact Phone Number: 555-1234
Contact Email: john@example.com
Event Name: Star Wars Day Celebration
Venue: Downtown Convention Center
Venue address: 123 Main Street, Tampa, FL 33602
Event Start: 05/04/2024 - 0900
Event End: 05/04/2024 - 1700
Event Website: https://example.com/event
Expected number of attendees: 500
Requested number of characters: 10
Requested character types: Stormtroopers, Vader
Secure changing/staging area: Yes
Can troopers carry blasters: Yes
Can troopers carry/bring props like lightsabers and staffs: Yes
Is parking available: Yes
Is venue accessible to those with limited mobility: Yes
Amenities available at venue: Restrooms, Water, Food
Comments: Looking forward to this event
Referred by: Jane Smith
EOT;

        $subject = TheLegionService::parseRequestAppearance($message);

        $this->assertInstanceOf(Event::class, $subject);
        $this->assertEquals('John Doe', $subject->contact_name);
        $this->assertEquals('555-1234', $subject->contact_phone);
        $this->assertEquals('john@example.com', $subject->contact_email);
        $this->assertEquals('Star Wars Day Celebration', $subject->name);
        $this->assertEquals('Downtown Convention Center', $subject->venue);
        $this->assertEquals('123 Main Street, Tampa, FL 33602', $subject->venue_address);
        $this->assertEquals('https://example.com/event', $subject->event_website);
        $this->assertEquals('500', $subject->expected_attendees);
        $this->assertEquals('10', $subject->requested_number_characters);
        $this->assertEquals('Stormtroopers, Vader', $subject->requested_character_types);
        $this->assertTrue($subject->secure_staging_area);
        $this->assertTrue($subject->allow_blasters);
        $this->assertTrue($subject->allow_props);
        $this->assertTrue($subject->parking_available);
        $this->assertTrue($subject->accessible);
        $this->assertEquals('Restrooms, Water, Food', $subject->amenities);
        $this->assertEquals('Looking forward to this event', $subject->comments);
        $this->assertEquals('Jane Smith', $subject->referred_by);
        $this->assertEquals($message, $subject->source);

        $this->assertInstanceOf(Carbon::class, $subject->event_start);
        $this->assertEquals('2024-05-04 09:00:00', $subject->event_start->format('Y-m-d H:i:s'));

        $this->assertInstanceOf(Carbon::class, $subject->event_end);
        $this->assertEquals('2024-05-04 17:00:00', $subject->event_end->format('Y-m-d H:i:s'));
    }

    public function test_parse_request_appearance_with_multiline_values(): void
    {
        $message = <<<EOT
Contact Name: John Doe
Event Name: Star Wars Day
This is a multi-line event name
that continues here
Venue: Convention Center
EOT;

        $subject = TheLegionService::parseRequestAppearance($message);

        $this->assertEquals('John Doe', $subject->contact_name);
        $this->assertEquals('Star Wars Day This is a multi-line event name that continues here', $subject->name);
        $this->assertEquals('Convention Center', $subject->venue);
    }

    public function test_parse_request_appearance_with_no_boolean_fields(): void
    {
        $message = <<<EOT
Contact Name: John Doe
Event Name: Test Event
Secure changing/staging area: No
Can troopers carry blasters: No
Can troopers carry/bring props like lightsabers and staffs: No
Is parking available: No
Is venue accessible to those with limited mobility: No
EOT;

        $subject = TheLegionService::parseRequestAppearance($message);

        $this->assertFalse($subject->secure_staging_area);
        $this->assertFalse($subject->allow_blasters);
        $this->assertFalse($subject->allow_props);
        $this->assertFalse($subject->parking_available);
        $this->assertFalse($subject->accessible);
    }

    public function test_parse_request_appearance_with_empty_lines(): void
    {
        $message = <<<EOT
Contact Name: John Doe

Event Name: Test Event

Venue: Test Venue
EOT;

        $subject = TheLegionService::parseRequestAppearance($message);

        $this->assertEquals('John Doe', $subject->contact_name);
        $this->assertEquals('Test Event', $subject->name);
        $this->assertEquals('Test Venue', $subject->venue);
    }

    public function test_parse_request_appearance_with_missing_fields(): void
    {
        $message = <<<EOT
Contact Name: John Doe
Event Name: Test Event
EOT;

        $subject = TheLegionService::parseRequestAppearance($message);

        $this->assertEquals('John Doe', $subject->contact_name);
        $this->assertEquals('Test Event', $subject->name);
        $this->assertNull($subject->contact_phone);
        $this->assertNull($subject->contact_email);
        $this->assertNull($subject->venue);
        $this->assertNull($subject->event_start);
        $this->assertNull($subject->event_end);
    }

    public function test_parse_request_appearance_preserves_source_message(): void
    {
        $message = <<<EOT
Contact Name: John Doe
Event Name: Test Event
EOT;

        $subject = TheLegionService::parseRequestAppearance($message);

        $this->assertEquals($message, $subject->source);
    }
}
