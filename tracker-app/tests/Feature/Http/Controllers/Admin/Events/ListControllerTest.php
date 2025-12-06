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

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('admin.events.list'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_administrator_can_see_all_events(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        Event::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.list'));

        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->count() === 3;
        });
    }

    public function test_moderator_sees_only_moderated_events(): void
    {
        $moderated_org = Organization::factory()->create();
        $unmoderated_org = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->withAssignment($moderated_org, moderator: true)->create();

        $moderated_event = Event::factory()->create([Event::ORGANIZATION_ID => $moderated_org->id]);
        Event::factory()->create([Event::ORGANIZATION_ID => $unmoderated_org->id]);

        $response = $this->actingAs($moderator)->get(route('admin.events.list'));

        $response->assertOk();
        $response->assertViewHas('events', function ($events) use ($moderated_event)
        {
            return $events->count() === 1 && $events->first()->id === $moderated_event->id;
        });
    }

    public function test_list_can_be_filtered_by_organization(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $org_a = Organization::factory()->create();
        $org_b = Organization::factory()->create();

        $event_a = Event::factory()->create([Event::ORGANIZATION_ID => $org_a->id]);
        Event::factory()->create([Event::ORGANIZATION_ID => $org_b->id]);

        $response = $this->actingAs($admin)->get(route('admin.events.list', ['organization_id' => $org_a->id]));

        $response->assertOk();
        $response->assertViewHas('events', function ($events) use ($event_a)
        {
            return $events->count() === 1 && $events->first()->id === $event_a->id;
        });
    }

    public function test_list_can_be_filtered_by_status(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $active_event = Event::factory()->open()->create();
        Event::factory()->closed()->create();

        $response = $this->actingAs($admin)->get(route('admin.events.list', ['status' => EventStatus::OPEN->value]));

        $response->assertOk();
        $response->assertViewHas('events', function ($events) use ($active_event)
        {
            return $events->count() === 1 && $events->first()->id === $active_event->id;
        });
    }

    public function test_list_can_be_filtered_by_search_term(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $event_to_find = Event::factory()->create(['name' => 'Unique Event Name Alpha']);
        Event::factory()->create(['name' => 'Another Event Beta']);

        $response = $this->actingAs($admin)->get(route('admin.events.list', ['search_term' => 'Unique Event']));

        $response->assertOk();
        $response->assertViewHas('events', function ($events) use ($event_to_find)
        {
            return $events->count() === 1 && $events->first()->id === $event_to_find->id;
        });
    }

    public function test_search_term_is_ignored_if_too_short(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        Event::factory()->create(['name' => 'Unique Event Name Alpha']);
        Event::factory()->create(['name' => 'Another Event Beta']);

        $response = $this->actingAs($admin)->get(route('admin.events.list', ['search_term' => 'Un']));

        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            return $events->count() === 2;
        });
    }

    public function test_non_admin_cannot_see_events_from_unmoderated_org_filter(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $unmoderated_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::MODERATOR => true,
        ]);

        Event::factory()->create([Event::ORGANIZATION_ID => $moderated_org->id]);
        Event::factory()->create([Event::ORGANIZATION_ID => $unmoderated_org->id]);

        // Try to filter by an org the user does not moderate
        $response = $this->actingAs($moderator)->get(route('admin.events.list', ['organization_id' => $unmoderated_org->id]));

        $response->assertOk();
        $response->assertViewHas('events', function ($events)
        {
            // The `moderatedBy` scope should prevent any results from showing
            return $events->count() === 0;
        });
    }
}