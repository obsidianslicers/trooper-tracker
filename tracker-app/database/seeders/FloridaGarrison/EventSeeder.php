<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    use HasSquadMaps;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $squad_maps = $this->getSquadMap();

        $legacy_events = DB::table('events')->get();

        foreach ($legacy_events as $event)
        {
            $e = Event::find($event->id) ?? new Event(['id' => $event->id]);

            $e->name = $event->name;
            $e->starts_at = $event->dateStart;
            $e->ends_at = $event->dateEnd;

            $e->limit_participants = $event->limitedEvent ?? false;
            $e->troopers_allowed = $event->limitedEvent ? $event->limitTotalTroopers : null;
            $e->handlers_allowed = $event->limitedEvent ? $event->limitHandlers : null;
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