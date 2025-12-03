<?php

declare(strict_types=1);

namespace Database\Seeders\Conversions;

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
        $settings = DB::table('settings')->first();

        $donate_goal = Setting::find('donate_goal') ?? new Setting(['key' => 'donate_goal']);

        $donate_goal->value = $settings->supportgoal;

        $donate_goal->save();

        Setting::firstOrCreate(['key' => 'site_name'], ['value' => '501st Florida Garrison']);
        Setting::firstOrCreate(['key' => 'forum_name'], ['value' => 'Florida Garrison']);
        Setting::firstOrCreate(['key' => 'forum_url'], ['value' => 'https://www.fl501st.com/boards/']);
        Setting::firstOrCreate(['key' => 'donate_url'], ['value' => 'https://www.fl501st.com/boards/account/upgrades']);
        Setting::firstOrCreate(['key' => 'webmaster'], ['value' => 'gwm@fl501st.com']);
    }
}