<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Carbon\Carbon;
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
    private $trooper_ids;
    private $handler_ids;

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

            $this->overlayOrganizations($legacy, $event);

            $this->overlayShifts($legacy, $event);
        }
    }

    private function initData()
    {
        $this->trooper_ids = Trooper::all()->pluck(Trooper::ID, Trooper::ID)->toArray();
        $this->handler_ids = Trooper::where(Trooper::MEMBERSHIP_ROLE, MembershipRole::HANDLER)->pluck(Trooper::ID, Trooper::ID)->toArray();
        $this->costumes = OrganizationCostume::all()->pluck(OrganizationCostume::ID)->toArray();
        $this->squad_maps = $this->getSquadMap();
        $this->trooper_status_map = array_values(array_filter(
            EventTrooperStatus::cases(),
            fn($case) => $case !== EventTrooperStatus::NONE
        ));
        $this->sign_ups = DB::table('event_sign_up')
            ->join('tt_troopers', 'event_sign_up.trooperid', '=', 'tt_troopers.id')
            ->join('tt_event_shifts', 'event_sign_up.troopid', '=', 'tt_event_shifts.id')
            ->select('event_sign_up.*')
            ->get();
    }

    private function overlayOrganization($legacy, $event)
    {
        $fl_garrison = $this->getOrganization(-1);

        if ($legacy->squad <= 0)
        {
            $event->organization_id = $fl_garrison->id;
        }
        else
        {
            $unit = $this->getOrganization($this->squad_maps[$legacy->squad]['id']);
            $event->organization_id = $unit->id;
        }
    }

    private function overlayOrganizations($legacy, $event)
    {
        $fl_garrison = $this->getOrganization(-1);

        $where = [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $fl_garrison->parent_id,
        ];

        $set = [
            EventOrganization::CAN_ATTEND => true,
        ];

        EventOrganization::updateOrCreate($where, $set);
    }

    private function overlayShifts($legacy, $event)
    {
        $shifts = collect([]);

        foreach ($legacy->shifts as $legacy_shift)
        {
            // Parse the incoming values
            $start = Carbon::parse($legacy_shift->dateStart);
            $end = Carbon::parse($legacy_shift->dateEnd);
            $key = $start->format('Y-m-d H:i');

            $filtered = $shifts->filter(fn($s) => $s->key === $key);

            if ($filtered->count() > 0)
            {
                //  move the duped troop onto the non-first record
                $shift = $filtered->first();

                $this->overlayTroopers($legacy_shift->id, $shift->id);
            }
            else
            {
                $shift = EventShift::find($legacy_shift->id) ?? new EventShift(['id' => $legacy_shift->id]);

                $shift->event_id = $event->id;
                $shift->status = $event->status;

                // Assign normalized values
                $shift->shift_starts_at = $start;
                $shift->shift_ends_at = $end;

                $shift->save();

                $shift->key = $key;

                $this->overlayTroopers($legacy_shift->id, $shift->id);

                $shifts->add($shift);
            }
        }
    }

    private function overlayTroopers($legacy_id, $shift_id)
    {
        $troopers = $this->sign_ups->filter(fn($s) => $s->troopid == $legacy_id);

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
                $e->status = $this->trooper_status_map[$sign_up->status];
                $e->signed_up_at = Carbon::parse($sign_up->signuptime);

                $e->is_handler = isset($this->handler_ids[$sign_up->trooperid]);

                if ($e->is_handler)
                {
                }
                else
                {
                    $e->costume_id = in_array($sign_up->costume, $this->costumes) ? $sign_up->costume : null;
                    $e->backup_costume_id = in_array($sign_up->costume_backup, $this->costumes) ? $sign_up->costume_backup : null;
                }

                if ($sign_up->addedby > 0)
                {
                    if (isset($this->trooper_ids[$sign_up->addedby]))
                    {
                        $e->added_by_trooper_id = $sign_up->addedby;
                    }
                }

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
            $event->troopers_allowed = max($legacy->limitTotalTroopers, $legacy->requestedNumber);

            if ($event->troopers_allowed == 500)
            {
                $event->troopers_allowed = null;
            }
        }
        else
        {
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
            ->where(function ($query)
            {
                $query->where('id', 10025)
                    ->orWhere('link', 10025);
            })
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