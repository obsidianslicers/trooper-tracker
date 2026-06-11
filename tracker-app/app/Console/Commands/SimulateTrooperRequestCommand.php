<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Features\Troopers\Commands\SubmitTrooperRequestCommand;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Illuminate\Console\Command;

class SimulateTrooperRequestCommand extends Command
{
    protected $signature = 'tracker:simulate-trooper-request
        {trooper_id? : Optional trooper ID — picks a random active trooper if omitted}';

    protected $description = 'Create a pending club join request for a trooper and output the admin review URL. Dev use only.';

    public function handle(MagicBus $bus): int
    {
        $trooper_id = $this->argument('trooper_id');

        if ($trooper_id !== null)
        {
            $trooper = Trooper::find($trooper_id);

            if (!$trooper)
            {
                $this->error("No trooper found with ID {$trooper_id}.");

                return self::FAILURE;
            }
        }
        else
        {
            $trooper = Trooper::where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
                ->inRandomOrder()
                ->first();

            if (!$trooper)
            {
                $this->error('No active troopers found in the database.');

                return self::FAILURE;
            }
        }

        $already = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->pluck(TrooperOrganization::ORGANIZATION_ID);

        $pending = TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING)
            ->pluck(TrooperRequest::ORGANIZATION_ID);

        $organization = Organization::where(Organization::DEPTH, '>', 0)
            ->whereNotIn(Organization::ID, $already->merge($pending))
            ->inRandomOrder()
            ->first();

        if (!$organization)
        {
            $this->error("No eligible organizations found for {$trooper->display_name} — they may already be active or have a pending request in all clubs.");

            return self::FAILURE;
        }

        $bus->send(new SubmitTrooperRequestCommand($trooper, $organization, null));

        $trooper_request = TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->where(TrooperRequest::ORGANIZATION_ID, $organization->id)
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING)
            ->latest()
            ->first();

        $org_label = $organization->parent
            ? "{$organization->parent->name} — {$organization->name}"
            : $organization->name;

        $review_url = route('admin.troopers.approvals');

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Trooper Request ───────────────────────────────</>');
        $this->info("  Trooper:       {$trooper->display_name} (ID {$trooper->id})");
        $this->info("  Organization:  {$org_label} (ID {$organization->id})");
        $this->info("  Request ID:    {$trooper_request->id}");
        $this->newLine();
        $this->info("  Review at: {$review_url}");
        $this->newLine();

        return self::SUCCESS;
    }
}
