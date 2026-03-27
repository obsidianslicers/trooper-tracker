<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\OrganizationType;
use App\Models\Observers\OrganizationObserver;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->loadRegions();
        $this->loadUnits();

        Organization::resequenceAll();

        //  model events are blocked during seeding, so we
        //  need to manually trigger the observer to set any
        //  derived fields
        $observer = new OrganizationObserver();
        $organizations = Organization::all();
        foreach ($organizations as $organization)
        {
            $observer->saved($organization);
        }
    }

    private function loadRegions()
    {
        $regions = [
            ['parent' => '501st Legion', 'name' => 'Florida Garrison'],
            ['parent' => 'Rebel Legion', 'name' => 'Ra Kura Base'],
            ['parent' => 'Mandalorian Mercs', 'name' => 'House Buurenaar Verda'],
            ['parent' => 'Dark Empire', 'name' => 'Dark Empire Florida'],
            ['parent' => 'Saber Guild', 'name' => 'Saber Guild - Talon Temple'],
            ['parent' => 'Droid Builders', 'name' => 'Florida Droid Builders'],
            ['parent' => 'Galactic Academy', 'name' => 'North America Coruscant Campus'],
        ];

        foreach ($regions as $data)
        {
            $parent = Organization::where('name', $data['parent'])->first();

            if ($parent)
            {
                $region = Organization::where('name', $data['name'])
                    ->where('parent_id', $parent->id)
                    ->first() ?? new Organization();

                $region->name = $data['name'];
                $region->parent_id = $parent->id;
                $region->type = OrganizationType::REGION;

                $region->save();
            }
        }
    }

    private function loadUnits()
    {
        $units = [
            ['region' => 'Florida Garrison', 'name' => 'Everglades Squad'],
            ['region' => 'Florida Garrison', 'name' => 'Makaze Squad'],
            ['region' => 'Florida Garrison', 'name' => 'Tampa Bay Squad'],
            ['region' => 'Florida Garrison', 'name' => 'Squad 7'],
            ['region' => 'Florida Garrison', 'name' => 'Parjai Squad'],
            ['region' => 'House Buurenaar Verda', 'name' => 'Aiwha Riders Clan'],
            ['region' => 'House Buurenaar Verda', 'name' => 'Batuu Clan'],
            ['region' => 'House Buurenaar Verda', 'name' => 'Drexl Clan'],
            ['region' => 'House Buurenaar Verda', 'name' => 'Scarif Clan'],
            ['region' => 'Dark Empire Florida', 'name' => 'Shadow Cell'],
            ['region' => 'Saber Guild - Talon Temple', 'name' => 'Performance Team'],
            ['region' => 'Florida Droid Builders', 'name' => 'R2 Builders Tampa'],
            ['region' => 'Florida Droid Builders', 'name' => 'R2 Builders Orlando'],
            ['region' => 'North America Coruscant Campus', 'name' => 'Florida Dagobah School'],
        ];

        foreach ($units as $data)
        {
            $region = Organization::where('name', $data['region'])->first();

            if ($region)
            {
                $unit = Organization::where('name', $data['name'])
                    ->where('parent_id', $region->id)
                    ->first() ?? new Organization();

                $unit->name = $data['name'];
                $unit->parent_id = $region->id;
                $unit->type = OrganizationType::UNIT;

                $unit->save();
            }
        }
    }
}