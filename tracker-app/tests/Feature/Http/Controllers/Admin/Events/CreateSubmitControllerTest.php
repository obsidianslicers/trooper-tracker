<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Jobs\SendEventCreatedNotificationsJob;
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
        $trooper = Trooper::factory()->asAdministrator()->create();

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

        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/create', [
            'organization_id' => $organization->id,
            'name' => 'Imperial Muster',
            'type' => EventType::REGULAR->value,
            'status' => EventStatus::OPEN->value,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
            'create_forum_thread' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_event_shifts', [
            'status' => EventStatus::OPEN->value,
        ]);
    }

    public function test_invoke_creates_event_with_blank_charity_funds(): void
    {
        Queue::fake([SendEventCreatedNotificationsJob::class]);

        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        // Simulate real form submission: blank strings for optional integer fields.
        // ConvertEmptyStringsToNull converts '' -> null before validation runs,
        // which previously caused a NOT NULL constraint violation on insert.
        $response = $this->actingAs($trooper)->post('/admin/events/create', [
            'organization_id' => $organization->id,
            'name' => 'Imperial Muster',
            'type' => EventType::REGULAR->value,
            'status' => EventStatus::DRAFT->value,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
            'create_forum_thread' => false,
            'charity_direct_funds' => '',
            'charity_indirect_funds' => '',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_events', [
            'charity_direct_funds' => 0,
            'charity_indirect_funds' => 0,
        ]);
    }
}
