<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Synchronizers;

use App\Models\Event;
use App\Services\Synchronizers\MandalorianMercsService;
use Carbon\Carbon;
use Tests\TestCase;

class MandalorianMercsServiceTest extends TestCase
{
    public function test_parse_request_appearance_with_full_data(): void
    {
        $message = <<<EOT
Name: Example Hoster
Phone: 407-555-9999
Email: hoster@example.org

Event Name: Marion County Maker Space
Event Location:
Ocala
Fl
United States

Event Date(s): 12/13/2025 to 12/13/2025

Start time:
End time:


Event Description:
This isn't necessarily an event request but a follow up to conversations I had with members at Maker Faire Orlando. We just opened a maker space and have members interested in builds. We would like to get started and offer our space

Website: example.org./makerspace

Number of attendees: (requester did not specify)

Can provide a safe and secure changing area?: Yes

How did you hear about us?: Maker Faire Orlando

Are our members allowed to carry prop/simulated firearms weapons such as blasters at your event?: Yes

Are our members allowed to carry prop/simulated melee weapons such as axes, knives, swords, or spears at your event?: Yes
EOT;

        $subject = MandalorianMercsService::parseRequestAppearance($message);

        $this->assertInstanceOf(Event::class, $subject);
        $this->assertEquals('Example Hoster', $subject->contact_name);
        $this->assertEquals('407-555-9999', $subject->contact_phone);
        $this->assertEquals('hoster@example.org', $subject->contact_email);
        $this->assertEquals('Marion County Maker Space', $subject->name);
        $this->assertEquals('Marion County Maker Space', $subject->venue);
        $this->assertEquals('Ocala Fl United States', $subject->venue_address);
        $this->assertEquals('example.org./makerspace', $subject->event_website);
        $this->assertNull($subject->expected_attendees);
        $this->assertTrue($subject->secure_staging_area);
        $this->assertTrue($subject->allow_blasters);
        $this->assertTrue($subject->allow_props);
        $this->assertEquals('Maker Faire Orlando', $subject->referred_by);
        $this->assertEquals($message, $subject->source);

        $this->assertInstanceOf(Carbon::class, $subject->event_start);
        $this->assertEquals('2025-12-13 00:00:00', $subject->event_start->format('Y-m-d H:i:s'));

        $this->assertInstanceOf(Carbon::class, $subject->event_end);
        $this->assertEquals('2025-12-13 00:00:00', $subject->event_end->format('Y-m-d H:i:s'));
    }

    public function test_parse_request_appearance_with_multiline_values(): void
    {
        $message = <<<EOT
Name: John Doe
Event Name: Star Wars Day
This is a multi-line event name
that continues here
Event Location:
Orlando
FL
United States
EOT;

        $subject = MandalorianMercsService::parseRequestAppearance($message);

        $this->assertEquals('John Doe', $subject->contact_name);
        $this->assertEquals('Star Wars Day This is a multi-line event name that continues here', $subject->name);
        $this->assertEquals('Orlando FL United States', $subject->venue_address);
    }

    public function test_parse_request_appearance_with_no_boolean_fields(): void
    {
        $message = <<<EOT
Name: John Doe
Event Name: Test Event
Can provide a safe and secure changing area?: No
Are our members allowed to carry prop/simulated firearms weapons such as blasters at your event?: No
Are our members allowed to carry prop/simulated melee weapons such as axes, knives, swords, or spears at your event?: No
EOT;

        $subject = MandalorianMercsService::parseRequestAppearance($message);

        $this->assertFalse($subject->secure_staging_area);
        $this->assertFalse($subject->allow_blasters);
        $this->assertFalse($subject->allow_props);
    }

    public function test_parse_request_appearance_with_empty_lines(): void
    {
        $message = <<<EOT
Name: John Doe

Event Name: Test Event

Event Location:
Tampa
FL
United States
EOT;

        $subject = MandalorianMercsService::parseRequestAppearance($message);

        $this->assertEquals('John Doe', $subject->contact_name);
        $this->assertEquals('Test Event', $subject->name);
        $this->assertEquals('Tampa FL United States', $subject->venue_address);
    }

    public function test_parse_request_appearance_with_missing_fields(): void
    {
        $message = <<<EOT
Name: John Doe
Event Name: Test Event
EOT;

        $subject = MandalorianMercsService::parseRequestAppearance($message);

        $this->assertEquals('John Doe', $subject->contact_name);
        $this->assertEquals('Test Event', $subject->name);
        $this->assertNull($subject->contact_phone);
        $this->assertNull($subject->contact_email);
        $this->assertNull($subject->venue_address);
        $this->assertNull($subject->event_start);
        $this->assertNull($subject->event_end);
    }

    public function test_parse_request_appearance_preserves_source_message(): void
    {
        $message = <<<EOT
Name: John Doe
Event Name: Test Event
EOT;

        $subject = MandalorianMercsService::parseRequestAppearance($message);

        $this->assertEquals($message, $subject->source);
    }
}
