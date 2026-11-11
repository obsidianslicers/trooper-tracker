<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\EventTypes\Pages;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_lists_event_types_in_order_for_administrators(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        EventType::query()->update([EventType::SORT_ORDER => 50]);
        $first = EventType::factory()->create([EventType::SORT_ORDER => 1]);
        Event::factory()->create([Event::EVENT_TYPE_ID => EventType::idNamed('Charity')]);

        $response = $this->actingAs($trooper)->get(route('admin.event-types.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/event-types/Index')
            ->has('event_types', 13)
            ->where('event_types.0.id', $first->id)
            ->where('event_types.0.can_delete', true)
            ->where('event_types', fn ($types) => collect($types)
                ->firstWhere('name', 'Charity')['can_delete'] === false));
    }

    public function test_invoke_allows_staff_officers(): void
    {
        $trooper = Trooper::factory()->asStaffOfficer()->create();

        $response = $this->actingAs($trooper)->get(route('admin.event-types.index'));

        $response->assertOk();
    }

    public function test_invoke_forbids_moderators(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.event-types.index'));

        $response->assertForbidden();
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.event-types.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
