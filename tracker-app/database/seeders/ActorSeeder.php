<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ActorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->admin();
        $this->makaze();    //moderator
    }

    private function admin()
    {
        $actor = Trooper::find(501) ?? new Trooper(['id' => 501]);

        $actor->display_name = 'Sith Lord';
        $actor->legal_name = 'Sith Lord';
        $actor->email = 'sith@sw.com';
        $actor->password = Hash::make('password');
        $actor->membership_status = MembershipStatus::ACTIVE;
        $actor->membership_role = MembershipRole::ADMINISTRATOR;

        $actor->save();

        if ($actor->organizations->count() == 0)
        {
            $organization = Organization::firstWhere(Organization::NAME, '501st Legion');

            $actor->organizations()->attach($organization->id, [
                'identifier' => '99_999-1',
            ]);

            $unit = Organization::firstWhere(Organization::NAME, 'Makaze Squad');

            $actor->trooper_assignments()->create([
                'organization_id' => $unit->id,
                'is_member' => true,
                'is_moderator' => true
            ]);
        }
    }

    private function makaze()
    {
        $actor = Trooper::find(502) ?? new Trooper(['id' => 502]);

        $actor->display_name = 'Sith Lord Junior';
        $actor->legal_name = 'Sith Lord Junior';
        $actor->email = 'sithjr@sw.com';
        $actor->password = Hash::make('password');
        $actor->membership_status = MembershipStatus::ACTIVE;
        $actor->membership_role = MembershipRole::MODERATOR;

        $actor->save();

        if ($actor->organizations->count() == 0)
        {
            $organization = Organization::firstWhere(Organization::NAME, '501st Legion');

            $actor->organizations()->attach($organization->id, [
                'identifier' => '99_999-2',
            ]);

            $unit = Organization::firstWhere(Organization::NAME, 'Makaze Squad');

            $actor->trooper_assignments()->create([
                'organization_id' => $unit->id,
                'is_member' => true,
                'is_moderator' => true
            ]);
        }
    }
}