<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Trooper;
use App\Models\Organization;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Award;
use App\Models\Notice;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    //    TODO: this a developer's seeder not associated
    //    with the FL Garrison - it's untested at the moment
    public function run(): void
    {
        // 1. Core Organizations (Hierarchy)
        $legion = Organization::factory()->create([
            Organization::NAME => 'Imperial Legion',
            Organization::TYPE => 'organization'
        ]);

        $garrison = Organization::factory()->create([
            Organization::NAME => '7th Legion Garrison',
            Organization::TYPE => 'organization',
            Organization::PARENT_ID => $legion->id
        ]);

        // 2. Organization Approved Costumes & Global Awards
        // Documentation fix: Using OrganizationCostume instead of Costume
        $costumes = OrganizationCostume::factory()->count(10)->create([
            OrganizationCostume::ORGANIZATION_ID => $garrison->id
        ]);

        $awards = Award::factory()->count(5)->create();

        // 3. The Ranks (Troopers)
        $troopers = Trooper::factory()->count(100)->create()->each(function ($trooper) use ($garrison, $costumes, $awards)
        {
            // Assign to Garrison via Pivot
            $trooper->organizations()->attach($garrison->id, [
                'status' => 'active',
                'verified_at' => now()->subMonths(6),
            ]);

            // Assign 1-2 approved costumes
            $trooper->costumes()->attach($costumes->random(rand(1, 2))->pluck(OrganizationCostume::ID), [
                'status' => 'approved',
            ]);

            // Award Merit
            if (rand(1, 10) > 8)
            {
                $trooper->awards()->attach($awards->random()->id, ['award_date' => now()]);
            }
        });

        // 4. Mission Log (Events)
        for ($i = 1; $i <= 40; $i++)
        {
            $date = Carbon::now()->subDays(rand(1, 365));
            $isPast = $date->isPast();

            $event = Event::factory()->create([
                Event::ORGANIZATION_ID => $garrison->id,
                Event::EVENT_START => $date,
                Event::EVENT_END => $date->copy()->addHours(4),
                Event::STATUS => $isPast ? EventStatus::CLOSED : EventStatus::OPEN,
                Event::CHARITY_DIRECT_FUNDS => $isPast ? rand(100, 1000) : 0,
            ]);

            // 5. Tactical Deployment (Shifts & Attendance)
            $shift = EventShift::factory()->create([
                EventShift::EVENT_ID => $event->id,
            ]);

            if ($event->status === EventStatus::CLOSED)
            {
                // Historical deployment data
                $attendees = $troopers->random(rand(3, 10));
                foreach ($attendees as $trooper)
                {
                    EventTrooper::create([
                        EventTrooper::EVENT_SHIFT_ID => $shift->id,
                        EventTrooper::TROOPER_ID => $trooper->id,
                        EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                    ]);
                }
            }
            else
            {
                // Future mission signups
                $signups = $troopers->random(rand(2, 5));
                foreach ($signups as $trooper)
                {
                    EventTrooper::create([
                        EventTrooper::EVENT_SHIFT_ID => $shift->id,
                        EventTrooper::TROOPER_ID => $trooper->id,
                        EventTrooper::STATUS => EventTrooperStatus::GOING,
                    ]);
                }
            }
        }

        // 6. Communications (Notices)
        Notice::factory()->count(5)->create([Notice::ORGANIZATION_ID => $garrison->id])->each(function ($notice) use ($troopers)
        {
            $readers = $troopers->random(intval($troopers->count() * 0.7));
            foreach ($readers as $reader)
            {
                // Using Model class reference for the pivot logic
                DB::table('tt_notice_troopers')->insert([
                    'notice_id' => $notice->id,
                    'trooper_id' => $reader->id,
                    'is_read' => true,
                    'created_at' => now(),
                ]);
            }
        });
    }
}
