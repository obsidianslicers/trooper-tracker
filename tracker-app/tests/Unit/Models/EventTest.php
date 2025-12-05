<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventRequest;
use Carbon\Carbon;
use Tests\TestCase;

class EventTest extends TestCase
{
    public function test_from_email(): void
    {
        $email_body = <<<EOT
Contact Name: Matthew Drennan
Contact Phone Number: (407) 967-1441
Contact Email: drennanmattheww@gmail.com
Event Name: Project Kid Connect
Venue: Lake County Sheriff's South District Office
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
Referred by: Matt Drennan TK52233
EOT;

        $subject = Event::fromEmail($email_body);

        $this->assertInstanceOf(Event::class, $subject);
        $this->assertEquals('Project Kid Connect', $subject->name);
        $this->assertInstanceOf(Carbon::class, $subject->starts_at);
        $this->assertEquals('2025-07-12 09:00:00', $subject->starts_at->toDateTimeString());
        $this->assertInstanceOf(Carbon::class, $subject->ends_at);
        $this->assertEquals('2025-07-12 12:00:00', $subject->ends_at->toDateTimeString());

        $this->assertTrue($subject->limit_organizations);
        $this->assertEquals(1000, $subject->troopers_allowed);
        $this->assertNull($subject->handlers_allowed);

        $this->assertInstanceOf(EventRequest::class, $subject->event_request);
        $this->assertEquals('Matthew Drennan', $subject->event_request->contact_name);
        $this->assertEquals('drennanmattheww@gmail.com', $subject->event_request->contact_email);
        $this->assertEquals('Project Kid Connect', $subject->event_request->event_name);
        $this->assertEquals('2025-07-12 09:00:00', $subject->event_request->event_start->toDateTimeString());
        $this->assertEquals('2025-07-12 12:00:00', $subject->event_request->event_end->toDateTimeString());
        $this->assertEquals(1000, $subject->event_request->expected_attendees);
        $this->assertTrue($subject->event_request->secure_staging_area);
    }
}