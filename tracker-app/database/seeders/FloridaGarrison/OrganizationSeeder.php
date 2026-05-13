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
            ['parent' => 'Dark Empire', 'name' => 'Spire of the Storm'],
            ['parent' => 'Saber Guild', 'name' => 'Dagobah Temple'],
            ['parent' => 'Saber Guild', 'name' => 'Takodana Temple'],
            ['parent' => 'Droid Builders', 'name' => 'Southern R2 Builders'],
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
            ['region' => 'Spire of the Storm', 'name' => 'Shadow Cell'],
            ['region' => 'House Buurenaar Verda', 'name' => 'Protectors of Lothal'],
            ['region' => 'Ra Kura Base', 'name' => 'NWFL'],
            ['region' => 'Ra Kura Base', 'name' => 'NEFL'],
            ['region' => 'Ra Kura Base', 'name' => 'WFL'],
            ['region' => 'Ra Kura Base', 'name' => 'CFL'],
            ['region' => 'Ra Kura Base', 'name' => 'SFL'],
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