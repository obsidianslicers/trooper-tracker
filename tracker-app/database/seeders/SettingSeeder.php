<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Setting::updateOrCreate(['key' => 'donate_goal'], ['value' => '300']);
        // Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'My Trooper Tracker']);
        // Setting::updateOrCreate(['key' => 'forum_name'], ['value' => 'The Trooper Tracker Forums']);
        // Setting::updateOrCreate(['key' => 'forum_url'], ['value' => 'https://forums.somewhere.com']);
        // Setting::updateOrCreate(['key' => 'donate_url'], ['value' => 'https://somewhere.com/donate']);
        // Setting::updateOrCreate(['key' => 'webmaster'], ['value' => 'webmaster@somewhere.com']);
    }
}