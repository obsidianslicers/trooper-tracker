<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_validation_errors_for_invalid_payload(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/create', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post('/admin/events/create', []);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_creates_event_shift_with_matching_event_status(): void
    {
        Queue::fake([SendEventCreatedNotificationsJob::class]);

        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/create', [
            'organization_id' => $organization->id,
            'name' => 'Imperial Muster',
            Event::EVENT_TYPE_ID => EventType::idNamed('Regular'),
            'status' => EventStatus::OPEN->value,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_event_shifts', [
            'status' => EventStatus::OPEN->value,
        ]);
    }

    public function test_invoke_rejects_an_event_type_hidden_from_the_form(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $hidden = EventType::factory()->inactive()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/create', [
            'organization_id' => $organization->id,
            'name' => 'Imperial Muster',
            Event::EVENT_TYPE_ID => $hidden->id,
            'status' => EventStatus::DRAFT->value,
            'event_start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'event_end' => now()->addDays(7)->addHours(4)->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors(Event::EVENT_TYPE_ID);
    }

}
