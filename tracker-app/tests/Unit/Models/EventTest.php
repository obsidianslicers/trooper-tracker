<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use Carbon\Carbon;
use Tests\TestCase;

class EventTest extends TestCase
{
    public function test_from_email(): void
    {
        $email_body = <<<EOT
Contact Name: Requestor
Contact Phone Number: (407) 555-1234
Contact Email: requestor@example.com
Event Name: Test Event
Venue: Test Venue
Venue address: 15855 State Rte 50 Clermont, Florida 34711
Clermont, Florida
34711
USA
Event Start: 07/12/2025 - 0900
Event End: 07/12/2025 - 1200
Event Website:
Expected number of attendees: 1000
Requested number of characters: 100
Requested character types:
Secure changing/staging area: Yes
Can troopers carry blasters: Yes
Can troopers carry/bring props like lightsabers and staffs: Yes
Is parking available: Yes
Is venue accessible to those with limited mobility: Yes
Amenities available at venue: Water, snacks, and a booth will be provided.
Comments: Community event with law enforcement and children and we give away school supplies.
Referred by: Requestor
EOT;

        $subject = Event::fromEmail($email_body);

        $this->assertInstanceOf(Event::class, $subject);
        $this->assertEquals('Test Event', $subject->name);
        $this->assertInstanceOf(Carbon::class, $subject->event_start);
        $this->assertEquals('2025-07-12 09:00:00', $subject->event_start->toDateTimeString());
        $this->assertInstanceOf(Carbon::class, $subject->event_end);
        $this->assertEquals('2025-07-12 12:00:00', $subject->event_end->toDateTimeString());

        $this->assertNull($subject->troopers_allowed);
        $this->assertNull($subject->handlers_allowed);

        $this->assertEquals('Requestor', $subject->contact_name);
        $this->assertEquals('requestor@example.com', $subject->contact_email);
        $this->assertEquals(1000, $subject->expected_attendees);
        $this->assertTrue($subject->secure_staging_area);
    }
}