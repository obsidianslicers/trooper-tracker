<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
 */
class DroidBuildersService extends BaseOrganizationService
{
    public function __construct(private readonly GoogleService $google, Organization $organization)
    {
        parent::__construct($organization);
    }

    public function syncCostumes(): void
    {
    }

    public function syncAllMembers(): void
    {
        $values = $this->google->getSheet("195NT1crFYL_ECVyzoaD2F1QXGW5WxlnBDfDaLVtM87Y", "Sheet1");

        for ($i = 1, $j = count($values); $i < $j; $i++)
        {
            $forum_id = $this->cleanInput($values[$i][0]);
            //         $droidname = cleanInput($value[1]);
            //         $imageurl = cleanInput($value[2]);

            $trooper = $this->organization->troopers()
                ->wherePivot(TrooperOrganization::IDENTIFIER, $forum_id)
                ->first();

            $this->updateTrooperStatus($trooper, $forum_id);
        }
    }

    public function syncMember(string $identifier): void
    {
        $this->syncAllMembers();
    }

    private function updateTrooperStatus(Trooper $trooper): void
    {
        $pivot = $trooper->pivot;

        $pivot->verified_at = now();
        $pivot->membership_status = MembershipStatus::ACTIVE;
        $pivot->save();
    }
}
