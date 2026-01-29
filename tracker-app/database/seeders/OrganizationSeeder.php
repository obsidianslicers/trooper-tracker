<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Observers\OrganizationObserver;
use App\Models\Organization;
use App\Services\Synchronizers\DarkEmpireServices;
use App\Services\Synchronizers\DroidBuildersService;
use App\Services\Synchronizers\MandalorianMercsService;
use App\Services\Synchronizers\RebelLegionService;
use App\Services\Synchronizers\SaberGuildServices;
use App\Services\Synchronizers\TheLegionService;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->loadOrganizations();
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

    private function loadOrganizations()
    {
        $organizations = [
            [
                'name' => '501st Legion',
                'description' => 'Imperial costuming organization focused on Star Wars villains.',
                'identifier_display' => 'TKID',
                'identifier_validation' => 'integer|between:1000,99999',
                'service_class' => TheLegionService::class,
                'sync_sheet_id' => null,
            ],
            [
                'name' => 'Rebel Legion',
                'description' => 'Rebel-aligned Star Wars costuming group.',
                'identifier_display' => 'Forum Username',
                'service_class' => RebelLegionService::class,
                'sync_sheet_id' => '1I3FuS_uPg2nuC80PEA6tKYaVBd1Qh1allTOdVz3M6x0'
            ],
            [
                'name' => 'Mandalorian Mercs',
                'description' => 'Custom Mandalorian armor builders and costumers.',
                'identifier_display' => 'CAT #',
                'identifier_validation' => 'integer',
                'service_class' => MandalorianMercsService::class,
                'sync_sheet_id' => null,
            ],
            [
                'name' => 'Dark Empire',
                'description' => 'Expanded universe costuming group for dark side characters.',
                'identifier_display' => '#',
                'identifier_validation' => 'integer',
                'service_class' => DarkEmpireServices::class,
                'sync_sheet_id' => null,
            ],
            [
                'name' => 'Droid Builders',
                'description' => 'Star Wars droid construction and robotics enthusiast group.',
                'identifier_display' => '#',
                'identifier_validation' => 'integer',
                'service_class' => DroidBuildersService::class,
                'sync_sheet_id' => null,
            ],
            [
                'name' => 'Saber Guild',
                'description' => 'Lightsaber performance and Jedi/Sith costuming group.',
                'identifier_display' => 'SG #',
                'identifier_validation' => 'integer',
                'service_class' => SaberGuildServices::class,
                'sync_sheet_id' => null,
            ],
        ];

        foreach ($organizations as $data)
        {
            $org = Organization::where('name', $data['name'])->first() ?? new Organization();

            $org->name = $data['name'];
            $org->service_class = $data['service_class'];
            $org->description = $data['description'];
            $org->identifier_display = $data['identifier_display'] ?? '';
            $org->identifier_validation = $data['identifier_validation'] ?? '';
            $org->type = OrganizationType::ORGANIZATION;

            $org->save();
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