<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Events\RemoveEventTrooperController
 */
class RemoveEventTrooperControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create();

        $response = $this->post(route('admin.events.troopers.remove', [$event, $event_trooper]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_removes_trooper_and_redirects(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.events.troopers.remove', [$event, $event_trooper]));

        $response->assertRedirect(route('admin.events.troopers', compact('event')));

        $this->assertSoftDeleted('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
        ]);
    }

    public function test_invoke_forbids_non_moderator(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create();

        $response = $this->actingAs($member)
            ->post(route('admin.events.troopers.remove', [$event, $event_trooper]));

        $response->assertForbidden();
    }
}
