<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Fix197 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->createVisitor();
        $this->createGuardian();
        $this->createChild();
    }

    private function createVisitor()
    {
        $visitor = Trooper::where(Trooper::EMAIL, 'visitor@sw.com')->first() ?? new Trooper();

        $visitor->display_name = 'Visitor';
        $visitor->legal_name = 'Visitor';
        $visitor->email = 'visitor@sw.com';
        $visitor->password = Hash::make('password');
        $visitor->membership_status = MembershipStatus::PENDING;
        $visitor->membership_role = MembershipRole::VISITOR;
        $visitor->email_verified_at = now();
        $visitor->setup_completed_at = now();
        $visitor->date_of_birth = now()->subYears(13);
        $visitor->guardian_id = Trooper::firstWhere(Trooper::EMAIL, 'guardian@sw.com')->id;

        $visitor->save();

        if ($visitor->organizations->count() == 0)
        {
            $organization = Organization::firstWhere(Organization::NAME, '501st Legion');

            $visitor->organizations()->attach($organization->id, [
                'identifier' => 'TK654321',
            ]);

            $visitor->trooper_assignments()->create([
                'organization_id' => $organization->id,
                'is_member' => true,
            ]);
        }
        else
        {
            $org = $visitor->organizations()->first();
            $org->pivot->membership_status = MembershipStatus::PENDING;
            $org->pivot->save();
        }
    }

    private function createGuardian()
    {
        $guardian = Trooper::where(Trooper::EMAIL, 'guardian@sw.com')->first() ?? new Trooper();

        $guardian->display_name = 'Requires Guardian';
        $guardian->legal_name = 'Requires Guardian';
        $guardian->email = 'guardian@sw.com';
        $guardian->password = Hash::make('password');
        $guardian->membership_status = MembershipStatus::ACTIVE;
        $guardian->membership_role = MembershipRole::MEMBER;
        $guardian->email_verified_at = now();
        $guardian->setup_completed_at = now();
        $guardian->save();
    }

    private function createChild()
    {
        $child = Trooper::where(Trooper::EMAIL, 'child@sw.com')->first() ?? new Trooper();

        $child->display_name = 'Child';
        $child->legal_name = 'Child';
        $child->email = 'child@sw.com';
        $child->password = Hash::make('password');
        $child->membership_status = MembershipStatus::PENDING;
        $child->membership_role = MembershipRole::MEMBER;
        $child->email_verified_at = now();
        $child->setup_completed_at = now();
        $child->date_of_birth = now()->subYears(13);
        $child->guardian_id = Trooper::firstWhere(Trooper::EMAIL, 'guardian@sw.com')->id;

        $child->save();

        if ($child->organizations->count() == 0)
        {
            $organization = Organization::firstWhere(Organization::NAME, 'Galactic Academy');

            $region = Organization::firstWhere(Organization::NAME, 'North America Coruscant Campus');

            $unit = Organization::firstWhere(Organization::NAME, 'Florida Dagobah School');

            $child->organizations()->attach($organization->id, [
                'identifier' => '654321',
            ]);

            $child->trooper_assignments()->create([
                'organization_id' => $unit->id,
                'is_member' => true,
            ]);
        }
        else
        {
            $org = $child->organizations()->first();
            $org->pivot->membership_status = MembershipStatus::PENDING;
            $org->pivot->save();
        }
    }
}