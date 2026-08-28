<?php

declare(strict_types=1);

namespace Database\Seeders\States;

use App\Messages\Troopers\Commands\Membership\CreateTrooperRequest;
use App\Models\Trooper;
use App\Models\Organization;
use App\Enums\MembershipStatus;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use App\Enums\TrooperRequestStatus;
use Illuminate\Database\Seeder;
use RuntimeException;

class CreateTrooperRequestState extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $trooper = $this->getTrooper();
        $organization = $this->getOrganization($trooper);

        CreateTrooperRequest::call(
            trooper: $trooper,
            organization: $organization,
        );

        $this->printHelp($trooper, $organization);
    }

    private function getTrooper(): Trooper
    {
        $trooper = Trooper::where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->inRandomOrder()
            ->first();

        if (!$trooper)
        {
            throw new RuntimeException('No active troopers found to simulate a request.');
        }

        return $trooper;
    }

    private function getOrganization(Trooper $trooper): Organization
    {
        $already = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->pluck(TrooperOrganization::ORGANIZATION_ID);

        $pending = TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING)
            ->pluck(TrooperRequest::ORGANIZATION_ID);

        $currently_attached = $already->merge($pending);

        $organization = Organization::query()
            ->fullyLoaded()
            ->whereNotIn(Organization::ID, $currently_attached)
            ->get()
            ->filter(fn(Organization $org) => $org->organizations->count() > 0)
            ->random();

        if (!$organization)
        {
            throw new RuntimeException("No eligible organizations found for {$trooper->display_name} — they may already be active or have a pending request in all clubs.");
        }

        return $organization;
    }

    private function printHelp(Trooper $trooper, Organization $organization): void
    {
        $review_url = route('admin.troopers.membership.approvals');

        $cmd = $this->command;

        if ($cmd)
        {
            $cmd->newLine();
            $cmd->line('<fg=cyan;options=bold>── Trooper Request ───────────────────────────────</>');
            $cmd->info("  Trooper:       {$trooper->display_name} #{$trooper->id}");
            $cmd->info("  Organization:  {$organization->name} #{$organization->id}");
            $cmd->newLine();
            $cmd->info("  Review at: {$review_url}");
            $cmd->newLine();
        }
    }

}
