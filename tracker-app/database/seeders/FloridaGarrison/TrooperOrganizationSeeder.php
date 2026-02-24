<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\MembershipStatus;
use App\Models\Base\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Database\Seeders\FloridaGarrison\Traits\HasEnumMaps;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrooperOrganizationSeeder extends Seeder
{
    use HasEnumMaps;
    use HasClubMaps;
    use HasSquadMaps;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legacy_troopers = DB::table('troopers')->get();

        foreach ($legacy_troopers as $trooper)
        {
            $this->assignOrganizationAndRegion($trooper);
            $this->assignUnit($trooper);
        }
    }

    private function assignOrganizationAndRegion($trooper)
    {
        $club_map = $this->getOrganizationClubMap();

        foreach ($club_map as $column => $club)
        {
            $legacy_id = $club['legacy_id'];

            if (!isset($club['id']))
            {
                //  can't map to the new club
                continue;
            }

            if ($club['permission_column'] == 'pDroid' && $trooper->{$club['permission_column']} < 1)
            {
                //  skip droid builders, 0 not a member
                //  no identifier column
                continue;
            }

            $organization = $this->getOrganization($club['id']);

            $identifier = '';

            if ($club['identity'] != '')
            {
                //  get the club identifier
                $identifier = $trooper->{$club['identity']};
            }

            $has_identifier = $identifier != null && $identifier != '' && $identifier != '0';

            if ($has_identifier)
            {
                $this->loadTrooperOrganization($trooper, $organization, $identifier);
            }

            //* 0 = Regular Member, 1 = Super Admin, 2 = Moderator, 3 = RIP Member, 4 = Handler
            $moderator = $trooper->permissions == 2 && ($trooper->{$column} == 1 || $trooper->{$column} == 2);
            $member = $has_identifier;
            $notify = $trooper->{'esquad' . $legacy_id} == 1;

            if (!$notify && !$moderator && !$member)
            {
                //  not getting notified, skip
                continue;
            }

            $region = $organization->organizations->first();

            $this->loadTrooperAssignment($trooper->id, $organization->id, $notify, false, false);
            $this->loadTrooperAssignment($trooper->id, $region->id, $notify, false, $moderator);
        }
    }

    private function assignUnit($trooper)
    {
        $squad_map = $this->getSquadMap();

        foreach ($squad_map as $legacy_id => $squad)
        {
            $notify = $trooper->{'esquad' . $legacy_id} == 1;

            $member = false;

            if ($trooper->squad == $legacy_id)
            {
                $member = true;
            }

            if (!$member && !$notify)
            {
                //  not a member and not notified
                continue;
            }

            $unit = $this->getOrganization($squad['id']);

            $region_assignment = TrooperAssignment::query()
                ->where(TrooperAssignment::ORGANIZATION_ID, $unit->parent_id)
                ->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                ->first();

            $moderator = $region_assignment->is_moderator ?? false;

            $this->loadTrooperAssignment($trooper->id, $unit->id, $notify || $member, $member, $moderator);
        }
    }

    private function loadTrooperOrganization($trooper, $organization, $identifier)
    {
        $exists = TrooperOrganization::query()
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->where(TrooperOrganization::IDENTIFIER, $identifier)
            ->exists();

        if ($exists)
        {
            //  already exists, skip
            return;
        }

        $trooper_org = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        if ($trooper_org === null)
        {
            $trooper_org = new TrooperOrganization();

            $trooper_org->trooper_id = $trooper->id;
            $trooper_org->organization_id = $organization->id;
        }

        $trooper_org->membership_status = MembershipStatus::ACTIVE;
        $trooper_org->identifier = $identifier;

        $trooper_org->save();
    }

    private function loadTrooperAssignment($trooper_id, $organization_id, $notify, $member, $moderator)
    {
        $trooper_assignment = TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $trooper_id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id)
            ->first();

        if ($trooper_assignment === null)
        {
            $trooper_assignment = new TrooperAssignment();

            $trooper_assignment->trooper_id = $trooper_id;
            $trooper_assignment->organization_id = $organization_id;
        }

        $trooper_assignment->should_notify = $notify;
        $trooper_assignment->is_member = $member;
        $trooper_assignment->is_moderator = $moderator;

        $trooper_assignment->save();
    }

    private function getOrganization($id)
    {
        $organizations = once(fn() => Organization::all()->keyBy('id'));

        return $organizations[$id];
    }
}