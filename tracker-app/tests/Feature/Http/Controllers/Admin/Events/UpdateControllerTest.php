<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_event_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.update', ['event' => $event->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.update');
    }

    public function test_invoke_offers_attendees_going_or_attended_as_coordinator(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $creator = Trooper::factory()->withDisplayName('TK-421 Creator')->withLegalName('Carla Creator')->create();
        $event = Event::factory()->withCreatedByTrooper($creator)->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $attendees = [
            ['TK-422 Going', 'Zoe Going', EventTrooperStatus::GOING],
            ['TK-424 Nameless', '', EventTrooperStatus::GOING],
            ['TK-423 Tentative', 'Tina Tentative', EventTrooperStatus::TENTATIVE],
        ];
        foreach ($attendees as [$display_name, $legal_name, $status])
        {
            EventTrooper::factory()->forEventShift($shift)
                ->forTrooper(Trooper::factory()->asEventCoordinator()->withDisplayName($display_name)->withLegalName($legal_name)->create())
                ->create([EventTrooper::STATUS => $status]);
        }

        //  only event coordinators and up can be picked
        EventTrooper::factory()->forEventShift($shift)
            ->forTrooper(Trooper::factory()->asMember()->withDisplayName('TK-425 Member')->create())
            ->create([EventTrooper::STATUS => EventTrooperStatus::GOING]);

        $response = $this->actingAs($admin)->get(route('admin.events.update', ['event' => $event->id]));

        //  name (username), sorted by what's shown; just the username when there's no name
        $response->assertOk();
        $response->assertSeeInOrder([
            'Event Coordinator',
            'Carla Creator (TK-421 Creator) (created the event)',
            'TK-424 Nameless',
            'Zoe Going (TK-422 Going)',
        ]);
        $response->assertDontSee('TK-423 Tentative');
        $response->assertDontSee('TK-425 Member');
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.update', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
