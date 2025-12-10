<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\EventStatus;
use App\Enums\TrooperEventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Database\Seeders\FloridaGarrison\Traits\HasEnumMaps;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    use HasSquadMaps;
    use HasEnumMaps;

    private $costumes;
    private $squad_maps;
    private $trooper_status_map;
    private $sign_ups;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->initData();

        $legacy_events = $this->getLegacyEvents();

        foreach ($legacy_events as $legacy)
        {
            $event = Event::find($legacy->id) ?? new Event(['id' => $legacy->id]);

            $this->overlayEvent($legacy, $event);
            $this->overlayOrganization($legacy, $event);

            $event->save();

            $this->overlayShifts($legacy, $event);
        }
    }

    private function initData()
    {
        $this->costumes = OrganizationCostume::all()->pluck(OrganizationCostume::ID)->toArray();
        $this->squad_maps = $this->getSquadMap();
        $this->trooper_status_map = array_values(array_filter(
            TrooperEventStatus::cases(),
            fn($case) => $case !== TrooperEventStatus::NONE
        ));
        $this->sign_ups = DB::table('event_sign_up')
            ->join('tt_troopers', 'event_sign_up.trooperid', '=', 'tt_troopers.id')
            ->join('tt_events', 'event_sign_up.troopid', '=', 'tt_events.id')
            ->select('event_sign_up.*')
            ->get();
    }

    private function overlayOrganization($legacy, $event)
    {
        if ($legacy->squad <= 0)
        {
            $unit = $this->getOrganization(-1);
            $event->organization_id = $unit->id;
        }
        else
        {
            $unit = $this->getOrganization($this->squad_maps[$legacy->squad]['id']);
            $event->organization_id = $unit->id;
        }

        //  TODO
        /*
        $organizations = Organization::all();

        foreach ($organizations as $organization)
        {
            EventOrganization::updateOrCreate(
                [
                    EventOrganization::EVENT_ID => $event->id,
                    EventOrganization::ORGANIZATION_ID => $organization->id,
                ],
                [
                    EventOrganization::CAN_ATTEND => true,
                    EventOrganization::TROOPERS_ALLOWED => null,
                    EventOrganization::HANDLERS_ALLOWED => null,
                ]);
        }*/
    }

    private function overlayShifts($legacy, $event)
    {
        $shifts = collect([]);

        foreach ($legacy->shifts as $x)
        {
            if ($shifts->where('shift_starts_at', $x->dateStart)->count())
            {
                //  move the duped troop onto the non-first record
                $shift = $shifts->where('shift_starts_at', $x->dateStart)->first();

                $this->overlayTroopers($x->id, $shift->id);
            }
            else
            {
                $shift = EventShift::find($x->id) ?? new EventShift(['id' => $x->id]);

                $shift->event_id = $event->id;
                $shift->status = $event->status;
                $shift->shift_starts_at = $x->dateStart;
                $shift->shift_ends_at = $x->dateEnd;

                $shift->save();

                $this->overlayTroopers($x->id, $shift->id);

                $shifts->add($shift);
            }
        }
    }

    private function overlayTroopers($legacy_id, $shift_id)
    {
        $troopers = $this->sign_ups->filter(fn($s) => $s->troopid === $legacy_id);

        foreach ($troopers as $sign_up)
        {
            $e = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
                ->where(EventTrooper::TROOPER_ID, $sign_up->trooperid)
                ->first();

            if ($e == null)
            {
                //  first record wins unforunately
                $e = new EventTrooper();

                $e->event_shift_id = $shift_id;
                $e->trooper_id = $sign_up->trooperid;
                $e->costume_id = in_array($sign_up->costume, $this->costumes) ? $sign_up->costume : null;
                $e->backup_costume_id = in_array($sign_up->costume_backup, $this->costumes) ? $sign_up->costume_backup : null;
                $e->status = $this->trooper_status_map[$sign_up->status];
                //TODO $e->added_by_trooper_id = $sign_up->addedby > 0 ? $sign_up->addedby : null;

                $e->save();
            }
        }
    }

    private function overlayEvent($legacy, $event)
    {
        $legacy->name = trim($legacy->name);

        $event->name = $legacy->name;

        $label = is_string($legacy->label) ? (int) $legacy->label : ($legacy->label ?? 0);

        $event->type = $this->eventLabelFromLegacyId($label);

        $event->venue = $legacy->venue;
        $event->venue_address = $legacy->location;
        $event->event_start = $legacy->dateStart;
        $event->event_end = $legacy->dateEnd;
        $event->event_website = $legacy->website;
        $event->expected_attendees = $legacy->numberOfAttend;
        $event->requested_characters = $legacy->requestedNumber;
        $event->requested_character_types = $legacy->requestedCharacter;
        $event->secure_staging_area = $legacy->secureChanging ?? false;
        $event->allow_blasters = $legacy->blasters ?? false;
        $event->allow_props = $legacy->lightsabers ?? false;
        $event->parking_available = $legacy->parking ?? false;
        $event->accessible = $legacy->mobility ?? false;
        $event->amenities = $legacy->amenities;
        $event->comments = $legacy->comments;
        $event->referred_by = $legacy->referred;

        if ($legacy->requestedNumber)
        {
            $event->has_organization_limits = true;
            $event->troopers_allowed = max($legacy->limitTotalTroopers, $legacy->requestedNumber);

            if ($event->troopers_allowed == 500)
            {
                $event->has_organization_limits = false;
                $event->troopers_allowed = null;
            }
        }
        else
        {
            $event->has_organization_limits = false;
            $event->troopers_allowed = null;
        }

        $event->status = $legacy->closed ? EventStatus::CLOSED : EventStatus::OPEN;

        $event->charity_direct_funds = $legacy->charityDirectFunds;
        $event->charity_indirect_funds = $legacy->charityIndirectFunds;
        $event->charity_name = $legacy->charityName;
        $event->charity_hours = $legacy->charityAddHours;
    }

    private function getOrganization($id)
    {
        if ($id <= 0)
        {
            $organization = once(fn() => Organization::where('name', 'Florida Garrison')->first());
        }
        else
        {
            $organization = once(fn() => Organization::findOrFail($id));
        }

        return $organization;
    }

    private function getLegacyEvents()
    {
        $all = DB::table('events')
            ->orderBy('id')
            ->orderBy('link')
            ->get();

        $events = [];

        foreach ($all->filter(fn($x) => $x->link === 0) as $event)
        {
            $event->shifts = collect([$event]);

            $events[$event->id] = $event;
        }

        foreach ($all->filter(fn($x) => $x->link > 0) as $event)
        {
            $main_event = $events[$event->link];

            $main_event->shifts->add($event);
        }

        return collect($events);
    }
}