<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventVenue;
use App\Models\Organization;
use Database\Seeders\FloridaGarrison\Traits\HasEnumMaps;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    use HasSquadMaps;
    use HasEnumMaps;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $squad_maps = $this->getSquadMap();

        $legacy_events = DB::table('events')->orderBy('id')->orderBy('link')->get();

        foreach ($legacy_events as $event)
        {
            $e = Event::find($event->id) ?? new Event(['id' => $event->id]);

            $event->name = trim($event->name);

            $e->name = $event->name;
            $e->starts_at = $event->dateStart;
            $e->ends_at = $event->dateEnd;

            $label = is_string($event->label) ? (int) $event->label : ($event->label ?? 0);

            $e->type = $this->eventLabelFromLegacyId($label);

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
            if ($event->link > 0)
            {
                $e->is_shift = true;
                $e->main_event_id = $event->link;
                $e->event_venue_id = $event->link;
            }
            else
            {
                $e->event_venue_id = $this->createEventVenue($event);
            }

            if ($event->requestedNumber)
            {
                $e->limit_organizations = true;
                $e->troopers_allowed = max($event->limitTotalTroopers, $event->requestedNumber);
                $e->handlers_allowed = $event->limitTotalTroopers - $event->requestedNumber;
            }
            else
            {
                $e->limit_organizations = false;
                $e->troopers_allowed = null;
                $e->handlers_allowed = null;
            }

            $e->status = $event->closed ? EventStatus::CLOSED : EventStatus::OPEN;

            $e->charity_direct_funds = $event->charityDirectFunds;
            $e->charity_indirect_funds = $event->charityIndirectFunds;
            $e->charity_name = $event->charityName;
            $e->charity_hours = $event->charityAddHours;

            if ($event->squad <= 0)
            {
                $unit = $this->getOrganization(-1);
                $e->organization_id = $unit->id;
            }
            else
            {
                $unit = $this->getOrganization($squad_maps[$event->squad]['id']);
                $e->organization_id = $unit->id;
            }

            $e->save();
        }
    }

    private function createEventVenue($event)
    {
        $event_venue = EventVenue::find($event->id) ?? new EventVenue(['id' => $event->id]);
        $event_venue->event_name = $event->name;
        $event_venue->venue = $event->venue;
        $event_venue->venue_address = $event->location;
        $event_venue->event_start = $event->dateStart;
        $event_venue->event_end = $event->dateEnd;
        $event_venue->event_website = $event->website;
        $event_venue->expected_attendees = $event->numberOfAttend;
        $event_venue->requested_characters = $event->requestedNumber;
        $event_venue->requested_character_types = $event->requestedCharacter;
        $event_venue->secure_staging_area = $event->secureChanging ?? false;
        $event_venue->allow_blasters = $event->blasters ?? false;
        $event_venue->allow_props = $event->lightsabers ?? false;
        $event_venue->parking_available = $event->parking ?? false;
        $event_venue->accessible = $event->mobility ?? false;
        $event_venue->amenities = $event->amenities;
        $event_venue->comments = $event->comments;
        $event_venue->referred_by = $event->referred;

        $event_venue->save();

        return $event_venue->id;
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
}