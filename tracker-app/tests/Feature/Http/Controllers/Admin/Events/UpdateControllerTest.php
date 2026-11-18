<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_the_event_page_with_every_tab(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.update', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/events/Update')
            ->where('event_id', $event->id)
            ->where('overview.form.name', $event->name)
            ->has('overview.host_options')
            ->has('overview.event_types')
            ->has('shifts')
            ->has('charity')
            ->has('roster.shifts')
            ->has('uploads')
            ->has('mission_review'));
    }

    public function test_invoke_lists_shifts_with_their_stations_in_order(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventShiftStation::factory()->forEventShift($shift)->create([
            EventShiftStation::NAME => 'Second',
            EventShiftStation::SEQUENCE => 20,
        ]);
        EventShiftStation::factory()->forEventShift($shift)->create([
            EventShiftStation::NAME => 'First',
            EventShiftStation::SEQUENCE => 10,
        ]);

        $response = $this->actingAs($trooper)->get(route('admin.events.update', compact('event')));

        $response->assertInertia(fn ($page) => $page
            ->where('shifts.0.id', $shift->id)
            ->where('shifts.0.starts_at', $shift->shift_starts_at->format('H:i'))
            ->where('shifts.0.stations.0.name', 'First')
            ->where('shifts.0.stations.1.name', 'Second'));
    }

    public function test_invoke_splits_uploads_between_admin_uploads_and_mission_review(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();
        $admin_upload = EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => true]);
        $member_upload = EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => false]);

        $response = $this->actingAs($trooper)->get(route('admin.events.update', compact('event')));

        $response->assertInertia(fn ($page) => $page
            ->has('uploads', 1)
            ->where('uploads.0.id', $admin_upload->id)
            ->has('mission_review', 1)
            ->where('mission_review.0.id', $member_upload->id));
    }

    public function test_old_tab_addresses_open_the_matching_tab(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();
        $update = route('admin.events.update', compact('event'));

        $tabs = [
            'admin.events.shifts' => 'shifts',
            'admin.events.charity' => 'charity',
            'admin.events.troopers' => 'roster',
            'admin.events.uploads' => 'uploads',
            'admin.events.mission-review' => 'mission-review',
        ];

        foreach ($tabs as $route => $tab)
        {
            $this->actingAs($trooper)->get(route($route, compact('event')))
                ->assertRedirect("{$update}?tab={$tab}");
        }
    }

    public function test_invoke_forbids_a_moderator_outside_the_event_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $event = Event::factory()->create();

        $this->actingAs($moderator)->get(route('admin.events.update', compact('event')))
            ->assertForbidden();
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
        $response->assertInertia(fn ($page) => $page
            ->where('overview.coordinator_creator', 'Carla Creator (TK-421 Creator) (created the event)')
            ->has('overview.coordinator_options', 2)
            ->where('overview.coordinator_options.0.label', 'TK-424 Nameless')
            ->where('overview.coordinator_options.1.label', 'Zoe Going (TK-422 Going)'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.update', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
