<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TrooperAchievement;
use Carbon\CarbonInterval;
use Database\Seeders\FloridaGarrison\AwardSeeder;
use Database\Seeders\FloridaGarrison\CostumeSeeder;
use Database\Seeders\FloridaGarrison\EventSeeder;
use Database\Seeders\FloridaGarrison\EventUploadSeeder;
use Database\Seeders\FloridaGarrison\EventUploadTrooperSeeder;
use Database\Seeders\FloridaGarrison\OrganizationCostumeSeeder;
use Database\Seeders\FloridaGarrison\OrganizationSeeder as FloridaGarrisonOrganizationSeeder;
use Database\Seeders\FloridaGarrison\TrooperAchievementSeeder;
use Database\Seeders\FloridaGarrison\TrooperCostumeSeeder;
use Database\Seeders\FloridaGarrison\TrooperDonationSeeder;
use Database\Seeders\FloridaGarrison\TrooperFriendSeeder;
use Database\Seeders\FloridaGarrison\TrooperOrganizationSeeder;
use Database\Seeders\FloridaGarrison\TrooperSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Artisan;

class FloridaGarrisonSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ms = Benchmark::measure(function ()
        {
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
            $this->call(TrooperFriendSeeder::class);

            $this->call(TrooperAchievementSeeder::class);
        });

        $readable = CarbonInterval::millisecond($ms)->cascade()->forHumans();

        $this->command->info("Florida Garrison seeding completed in {$readable}.");

        Artisan::call('tracker:synchronize-organizations');
    }
}
