<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\GoogleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock GoogleService for all tests to avoid external API calls
        $google_mock = $this->createMock(GoogleService::class);
        $google_mock->expects($this->any())
            ->method('getLatitudeLongitude')
            ->willReturn([28.5494, -81.7828]); // Default Clermont, FL coordinates

        $this->app->instance(GoogleService::class, $google_mock);
    }

    public function test_invoke_creates_event_from_valid_form_submission(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'source' => $this->getEmailBody(),
            'organization_id' => $organization->id,
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where('name', 'Test Event')->first();
        $response->assertRedirect(route('admin.events.update', $event));
        $this->assertDatabaseHas(Event::class, [
            'name' => 'Test Event',
            'venue' => 'Test Venue',
            'status' => EventStatus::DRAFT->value,
        ]);
    }

    public function test_invoke_creates_event_from_email_source(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'source' => $this->getEmailBody(),
            'organization_id' => $organization->id,
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.create'), $event_data);

        // Assert
        $this->assertDatabaseHas(Event::class, [
            'name' => 'Test Event',
        ]);
    }

    public function test_invoke_creates_event_organization_association(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'source' => $this->getEmailBody(),
            'organization_id' => $organization->id,
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where('name', 'Test Event')->first();
        $this->assertNotNull($event);
        $this->assertDatabaseHas(EventOrganization::class, [
            'event_id' => $event->id,
            'organization_id' => $organization->id,
            'can_attend' => true,
        ]);
    }

    public function test_invoke_creates_initial_event_shift(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $start_time = Carbon::createFromFormat('Y-m-d H:i', '2025-07-12 09:00');
        $end_time = Carbon::createFromFormat('Y-m-d H:i', '2025-07-12 12:00');

        $event_data = [
            'source' => $this->getEmailBody(),
            'organization_id' => $organization->id,
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where('name', 'Test Event')->first();
        $this->assertDatabaseHas(EventShift::class, [
            'event_id' => $event->id,
        ]);

        $shift = EventShift::where('event_id', $event->id)->first();
        $this->assertEquals($start_time->format('Y-m-d H:i'), $shift->shift_starts_at->format('Y-m-d H:i'));
        $this->assertEquals($end_time->format('Y-m-d H:i'), $shift->shift_ends_at->format('Y-m-d H:i'));
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'source' => $this->getEmailBody(),
            'organization_id' => $organization->id,
        ];

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.events.create'), $event_data);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseMissing(Event::class, [
            'name' => 'Test Event',
        ]);
    }

    public function test_invoke_stores_latitude_and_longitude_from_google_service(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $expected_latitude = 28.5494;
        $expected_longitude = -81.7828;

        // Create a fresh mock for this specific test
        $google_mock = $this->createMock(GoogleService::class);
        $google_mock->expects($this->once())
            ->method('getLatitudeLongitude')
            ->with('15855 State Rte 50 Clermont, Florida 34711, Clermont, Florida 34711, USA')
            ->willReturn([$expected_latitude, $expected_longitude]);

        $this->app->instance(GoogleService::class, $google_mock);

        $event_data = [
            'source' => $this->getEmailBody(),
            'organization_id' => $organization->id,
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where('name', 'Test Event')->first();
        $this->assertNotNull($event);
        $this->assertEquals($expected_latitude, $event->latitude);
        $this->assertEquals($expected_longitude, $event->longitude);
    }

    private function getEmailBody(): string
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
Referred by: Matt Drennan TK52233
EOT;

        return $email_body;
    }
}
