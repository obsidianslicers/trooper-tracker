<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Retires troopers who have no valid email address.
 *
 * Any trooper without a valid email cannot be contacted or use email-based login recovery,
 * so they are moved to retired status both globally and in every organization they belong to.
 */
class Fix394 extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void
        {
            $trooper_ids = $this->retireTroopersWithoutEmail();
            $organizations_retired = $this->retireOrganizationMemberships($trooper_ids);

            $this->command?->info('Fix394 complete:');
            $this->command?->info("  Retired {$trooper_ids->count()} trooper(s) with no valid email.");
            $this->command?->info("  Retired {$organizations_retired} organization membership(s) for those trooper(s).");
        });
    }

    private function retireTroopersWithoutEmail(): Collection
    {
        $trooper_ids = Trooper::where(Trooper::MEMBERSHIP_STATUS, '!=', MembershipStatus::RETIRED)
            ->get()
            ->reject(fn(Trooper $trooper) => $trooper->emailAppearsValid())
            ->pluck(Trooper::ID);

        if ($trooper_ids->isEmpty())
        {
            return $trooper_ids;
        }

        Trooper::whereIn(Trooper::ID, $trooper_ids)
            ->update([Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED]);

        return $trooper_ids;
    }

    private function retireOrganizationMemberships(Collection $trooper_ids): int
    {
        if ($trooper_ids->isEmpty())
        {
            return 0;
        }

        return TrooperOrganization::whereIn(TrooperOrganization::TROOPER_ID, $trooper_ids)
            ->where(TrooperOrganization::MEMBERSHIP_STATUS, '!=', MembershipStatus::RETIRED)
            ->whereNull(TrooperOrganization::DELETED_AT)
            ->update([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED]);
    }
}
