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

/**
 * Feature tests for Admin Events ListController.
 *
 * Verifies:
 * - Administrators can view all events
 * - Moderators can view only events from their organizations
 * - Event filtering by status works correctly
 * - Event filtering by organization works correctly
 * - Event search by name works correctly
 * - Pagination works correctly
 * - View displays correct data structure
 * - Authentication is required
 */
class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.events.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_event_list_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.list');
    }

    public function test_invoke_administrator_sees_all_events(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(3)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 3;
        });
    }

    public function test_invoke_moderator_sees_only_moderated_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        // Organization the moderator has access to
        $moderated_org = Organization::factory()->create();
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Organization the moderator does NOT have access to
        $other_org = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $moderated_org->id,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $other_org->id,
        ]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 2;
        });
    }

    public function test_invoke_filters_by_event_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::STATUS => EventStatus::OPEN,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::STATUS => EventStatus::CLOSED,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'status' => EventStatus::OPEN->value,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 2;
        });
        $response->assertViewHas('status', EventStatus::OPEN->value);
    }

    public function test_invoke_filters_by_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $org1->id,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $org2->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'organization_id' => $org1->id,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 2;
        });
        $response->assertViewHas('organization', function ($organization) use ($org1)
        {
            return $organization->id === $org1->id;
        });
    }

    public function test_invoke_searches_by_event_name(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'Star Wars Convention',
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'Comic Con',
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'search_term' => 'Star Wars',
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 1;
        });
        $response->assertViewHas('search_term', 'Star Wars');
    }

    public function test_invoke_combines_multiple_filters(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Event::factory()->create([
            Event::ORGANIZATION_ID => $org1->id,
            Event::STATUS => EventStatus::OPEN,
            Event::NAME => 'Target Event',
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $org1->id,
            Event::STATUS => EventStatus::CLOSED,
            Event::NAME => 'Target Event 2',
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $org2->id,
            Event::STATUS => EventStatus::OPEN,
            Event::NAME => 'Other Event',
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'organization_id' => $org1->id,
            'status' => EventStatus::OPEN->value,
            'search_term' => 'Target',
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 1;
        });
    }

    public function test_invoke_provides_status_options_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('status_options');
        $response->assertViewHas('status_options', function ($options)
        {
            return is_array($options) && count($options) > 0;
        });
    }

    public function test_invoke_paginates_results(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(20)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 20 && $events->perPage() === 15;
        });
    }

    public function test_invoke_supports_pagination_navigation(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(20)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act - Get page 2
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'page' => 2,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->currentPage() === 2;
        });
    }

    public function test_invoke_returns_empty_events_when_none_exist(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 0;
        });
    }

    public function test_invoke_sets_organization_to_null_when_not_filtered(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organization', null);
    }

    public function test_invoke_sets_status_to_null_when_not_filtered(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('status', null);
    }

    public function test_invoke_sets_search_term_to_null_when_not_provided(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('search_term', null);
    }

    public function test_invoke_throws_not_found_for_invalid_organization_id(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'organization_id' => 999999,
        ]));

        // Assert
        $response->assertNotFound();
    }

    public function test_invoke_passes_all_required_data_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events');
        $response->assertViewHas('organization');
        $response->assertViewHas('status');
        $response->assertViewHas('search_term');
        $response->assertViewHas('status_options');
    }

    public function test_invoke_preserves_query_string_in_pagination(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(20)->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::STATUS => EventStatus::OPEN,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'status' => EventStatus::OPEN->value,
            'search_term' => 'test',
        ]));

        // Assert
        $response->assertOk();
        // The paginator should preserve the query string
        $response->assertViewHas('events', function ($events)
        {
            $url = $events->url(2);
            return str_contains($url, 'status=') && str_contains($url, 'search_term=');
        });
    }

    public function test_invoke_moderator_cannot_see_events_without_assignment(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(3)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->total() === 0;
        });
    }

    public function test_invoke_includes_event_shifts_count(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        \App\Models\EventShift::factory()->count(3)->create([
            \App\Models\EventShift::EVENT_ID => $event->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            $first_event = $events->items()[0];
            return isset($first_event->event_shifts_count) && $first_event->event_shifts_count === 3;
        });
    }
}
