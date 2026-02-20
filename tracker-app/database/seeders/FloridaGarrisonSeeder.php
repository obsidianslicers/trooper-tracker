<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\FloridaGarrison\AwardSeeder;
use Database\Seeders\FloridaGarrison\CostumeSeeder;
use Database\Seeders\FloridaGarrison\EventSeeder;
use Database\Seeders\FloridaGarrison\EventUploadSeeder;
use Database\Seeders\FloridaGarrison\EventUploadTrooperSeeder;
use Database\Seeders\FloridaGarrison\OrganizationCostumeSeeder;
use Database\Seeders\FloridaGarrison\OrganizationSeeder as FloridaGarrisonOrganizationSeeder;
use Database\Seeders\FloridaGarrison\TrooperCostumeSeeder;
use Database\Seeders\FloridaGarrison\TrooperDonationSeeder;
use Database\Seeders\FloridaGarrison\TrooperOrganizationSeeder;
use Database\Seeders\FloridaGarrison\TrooperSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class FloridaGarrisonSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $start = microtime(true);

        $this->call(CostumeSeeder::class);
        $this->call(OrganizationSeeder::class);
        $this->call(FloridaGarrisonOrganizationSeeder::class);
        $this->call(OrganizationCostumeSeeder::class);

        $this->call(TrooperSeeder::class);
        $this->call(TrooperDonationSeeder::class);
        $this->call(TrooperOrganizationSeeder::class);
        $this->call(TrooperCostumeSeeder::class);

        $this->call(AwardSeeder::class);

        if (config('app.debug') === true)
        {
            $this->call(ActorSeeder::class);
        }

        $this->call(EventSeeder::class);
        $this->call(EventUploadSeeder::class);
        $this->call(EventUploadTrooperSeeder::class);

        $end = microtime(true);

        $duration = $end - $start;

        $this->command->info("Florida Garrison seeding completed in {$duration} seconds.");

        Artisan::call('tracker:calculate-trooper-achievements');
    }
}
