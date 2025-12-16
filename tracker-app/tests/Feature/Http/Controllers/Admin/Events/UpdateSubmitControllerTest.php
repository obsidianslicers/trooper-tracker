<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_event_from_valid_form_submission(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            'name' => 'Original Name',
            'venue' => 'Original Venue',
        ]);

        $update_data = [
            'name' => 'Updated Name',
            'status' => EventStatus::OPEN->value,
            'venue' => 'Updated Venue',
            'venue_address' => '123 Main St',
            'venue_city' => 'Test City',
            'venue_state' => 'TS',
            'venue_zip' => '12345',
            'venue_country' => 'US',
            'event_start' => Carbon::now()->addDays(10)->format('Y-m-d H:i:s'),
            'event_end' => Carbon::now()->addDays(10)->addHours(2)->format('Y-m-d H:i:s'),
            'troopers_allowed' => 50,
            'handlers_allowed' => 10,
            'contact_name' => 'John Doe',
            'contact_phone' => '555-1234',
            'contact_email' => 'john@example.com',
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
            'expected_attendees' => 100,
            'requested_characters' => 9,
            'requested_character_types' => 'Imperial',
            'secure_staging_area' => true,
            'allow_blasters' => true,
            'allow_props' => true,
            'parking_available' => true,
            'accessible' => true,
            'amenities' => 'Restrooms, Water',
            'comments' => 'Test comments',
            'referred_by' => 'Friend',
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), $update_data);

        // Assert
        $this->assertDatabaseHas(Event::class, [
            'id' => $event->id,
            'name' => 'Updated Name',
            'venue' => 'Updated Venue',
            'status' => EventStatus::OPEN->value,
        ]);

        $response->assertRedirect(route('admin.events.update', $event));
    }

    public function test_invoke_updates_organization_associations(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();

        $event = Event::factory()->withOrganization($organization)->create();

        EventOrganization::create([
            'event_id' => $event->id,
            'organization_id' => $organization->id,
            'can_attend' => true,
        ]);
        EventOrganization::create([
            'event_id' => $event->id,
            'organization_id' => $organization_2->id,
            'can_attend' => false,
        ]);

        $update_data = Event::factory()->make()->toArray();
        $update_data['organizations'] = [
            $organization_1->id => ['can_attend' => false],
            $organization_2->id => ['can_attend' => true],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), $update_data);

        // Assert
        $this->assertDatabaseHas(EventOrganization::class, [
            'event_id' => $event->id,
            'organization_id' => $organization_1->id,
            'can_attend' => false,
        ]);

        $this->assertDatabaseHas(EventOrganization::class, [
            'event_id' => $event->id,
            'organization_id' => $organization_2->id,
            'can_attend' => true,
        ]);
    }

    public function test_invoke_updates_event_coordinates(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        $update_data = Event::factory()->make([
            'latitude' => '34.0522',
            'longitude' => '-118.2437',
        ])->toArray();
        $update_data['organizations'] = [];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), $update_data);

        // Assert
        $this->assertDatabaseHas(Event::class, [
            'id' => $event->id,
            'latitude' => '34.0522',
            'longitude' => '-118.2437',
        ]);
    }

    public function test_invoke_updates_event_amenities(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $update_data = Event::factory()->make([
            'secure_staging_area' => true,
            'allow_blasters' => true,
            'allow_props' => false,
            'parking_available' => true,
            'accessible' => true,
        ])->toArray();
        $update_data['organizations'] = [];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), $update_data);

        // Assert
        $this->assertDatabaseHas(Event::class, [
            'id' => $event->id,
            'secure_staging_area' => true,
            'allow_blasters' => true,
            'allow_props' => false,
            'parking_available' => true,
            'accessible' => true,
        ]);
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create(['name' => 'Original Name']);

        $update_data = Event::factory()->make(['name' => 'Updated Name'])->toArray();
        $update_data['organizations'] = [];

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.events.update', $event), $update_data);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Event::class, [
            'id' => $event->id,
            'name' => 'Original Name',
        ]);
    }
}
