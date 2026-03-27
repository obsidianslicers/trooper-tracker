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
        $this->requiresGuardian();
        $this->makaze();    //moderator
    }

    private function admin()
    {
        $actor = new Trooper();

        $actor->display_name = 'Sith Lord';
        $actor->legal_name = 'Sith Lord';
        $actor->email = 'sith@sw.com';
        $actor->password = Hash::make('password');
        $actor->membership_status = MembershipStatus::ACTIVE;
        $actor->membership_role = MembershipRole::ADMINISTRATOR;
        $actor->email_verified_at = now();

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

    private function requiresGuardian()
    {
        $actor = new Trooper();

        $actor->display_name = 'Sith Lord Jr';
        $actor->legal_name = 'Sith Lord Jr';
        $actor->email = 'sith-jr@sw.com';
        $actor->password = Hash::make('password');
        $actor->membership_status = MembershipStatus::ACTIVE;
        $actor->membership_role = MembershipRole::MEMBER;
        $actor->email_verified_at = now();
        $actor->setup_completed_at = now();
        $actor->date_of_birth = now()->subYears(14);
        $actor->guardian_id = Trooper::firstWhere(Trooper::EMAIL, 'sith@sw.com')->id;

        $actor->save();

        if ($actor->organizations->count() == 0)
        {
            $organization = Organization::firstWhere(Organization::NAME, 'Galactic Academy');

            $actor->organizations()->attach($organization->id, [
                'identifier' => '99_999-1',
            ]);

            $unit = Organization::firstWhere(Organization::NAME, 'Florida Dagobah School');

            $actor->trooper_assignments()->create([
                'organization_id' => $unit->id,
                'is_member' => true,
            ]);
        }
    }

    private function makaze()
    {
        $actor = new Trooper();

        $actor->display_name = 'Sith Moderator';
        $actor->legal_name = 'Sith Moderator';
        $actor->email = 'sith-moderator@sw.com';
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