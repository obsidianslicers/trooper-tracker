<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Enums\MembershipRole;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;


class Fix253 extends Seeder
{
    public function run(): void
    {
        $event = Event::where(Event::NAME, 'Test Event for Fix253')->first() ?? new Event();

        $event->name = 'Test Event for Fix253';
        $event->event_start = now()->subHour();
        $event->event_end = now()->addHours(2);
        $event->organization_id = 1;
        $event->save();

        $shift = EventShift::where(EventShift::EVENT_ID, $event->id)->first() ?? new EventShift();

        $shift->event_id = $event->id;
        $shift->shift_starts_at = now()->subHour();
        $shift->shift_ends_at = now()->addHours(2);
        $shift->save();

        $user = Trooper::where(Trooper::EMAIL, 'fix253@sw.com')->first() ?? new Trooper();

        $user->email = 'fix253@sw.com';
        $user->legal_name = 'Test Trooper for Fix253';
        $user->display_name = 'Test Trooper for Fix253';
        $user->password = Hash::make('password');
        $user->membership_role = MembershipRole::MEMBER;
        $user->email_verified_at = now();
        $user->setup_completed_at = now();
        $user->save();
    }
}
