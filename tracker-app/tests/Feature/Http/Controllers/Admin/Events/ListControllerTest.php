<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_list_for_admin(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Event::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.list');
        $response->assertViewHas('events');
        $response->assertViewHas('status_options');
    }

    public function test_invoke_displays_event_list_for_moderator(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        Event::factory()->count(2)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.list');
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.events.list'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_filters_events_by_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Event::factory()->create(['status' => EventStatus::DRAFT]);
        Event::factory()->create(['status' => EventStatus::OPEN]);
        Event::factory()->create(['status' => EventStatus::CANCELLED]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'status' => EventStatus::OPEN,
        ]));

        // Assert
        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(1, $events);
        $this->assertEquals(EventStatus::OPEN, $events->first()->status);
    }

    public function test_invoke_filters_events_by_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();

        $event_1 = Event::factory()->withOrganization($organization_1)->create();
        $event_2 = Event::factory()->withOrganization($organization_2)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'organization_id' => $organization_1->id,
        ]));

        // Assert
        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(1, $events);
        $this->assertEquals($event_1->id, $events->first()->id);
    }

    public function test_invoke_searches_events_by_name(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Event::factory()->create(['name' => 'Star Wars Convention']);
        Event::factory()->create(['name' => 'Comic Book Expo']);
        Event::factory()->create(['name' => 'Star Trek Festival']);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list', [
            'search_term' => 'Star',
        ]));

        // Assert
        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(2, $events);
    }

    public function test_invoke_paginates_event_results(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Event::factory()->count(25)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertLessThanOrEqual(20, $events->count());
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $events);
    }

    public function test_invoke_includes_breadcrumb_navigation(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $response->assertSee('Command Staff');
    }

    public function test_invoke_handles_empty_event_list(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        // Assert
        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(0, $events);
    }
}
