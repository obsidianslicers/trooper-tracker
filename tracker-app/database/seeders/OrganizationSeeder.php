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
                'sync_sheet_id' => '1I3FuS_uPg2nuC80PEA6tKYaVBd1Qh1allTOdVz3M6x0',
                'identifier_validation' => 'string|max:64',
                'service_class' => RebelLegionService::class
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
                'sync_sheet_id' => '195NT1crFYL_ECVyzoaD2F1QXGW5WxlnBDfDaLVtM87Y',
            ],
            [
                'name' => 'Saber Guild',
                'description' => 'Lightsaber performance and Jedi/Sith costuming group.',
                'identifier_display' => 'SG #',
                'identifier_validation' => 'integer',
                'service_class' => SaberGuildServices::class,
                'sync_sheet_id' => '1PcveycMujakkKeG2m4y8iFunrFbo2KVpQJ00GyPI3b8',
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
}