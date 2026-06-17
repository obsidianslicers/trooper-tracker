<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SimulateShiftCompleteCommand extends Command
{
    protected $signature = 'tracker:simulate-shift-complete
        {trooper_id : ID of the trooper to generate a test scenario for}
        {--expired : Shift ended more than 30 days ago (outside the update window; default is within window)}
        {--dual-costume : Trigger club-selection with 2 distinct top-level parent orgs}
        {--triple-costume : Trigger club-selection with 3 distinct top-level parent orgs}
        {--no-eligible-orgs : Produce a scenario where no organizations are eligible for credit}';

    protected $description = 'Create a test shift-complete scenario for a trooper and output the confirmation URLs. Dev use only.';

    public function handle(): int
    {
        $trooper = Trooper::find($this->argument('trooper_id'));

        if (!$trooper)
        {
            $this->error("No trooper found with ID {$this->argument('trooper_id')}.");

            return self::FAILURE;
        }

        $expired = $this->option('expired');
        $dual_costume = $this->option('dual-costume');
        $triple_costume = $this->option('triple-costume');
        $no_eligible = $this->option('no-eligible-orgs');

        if ($dual_costume && $triple_costume)
        {
            $this->error('--dual-costume and --triple-costume are mutually exclusive.');

            return self::FAILURE;
        }

        if ($no_eligible && ($dual_costume || $triple_costume))
        {
            $this->error('--no-eligible-orgs cannot be combined with --dual-costume or --triple-costume.');

            return self::FAILURE;
        }

        $member_orgs = Organization::whereHas('trooper_assignments', fn ($q) => $q->where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::IS_MEMBER, true)
        )->get();

        if ($member_orgs->isEmpty())
        {
            $this->warn("Trooper {$trooper->display_name} has no active club memberships.");
            $this->warn('Creating a standalone org for the event — credit attribution will use default logic.');
            $primary_org = Organization::factory()->create(['name' => 'Test Garrison (auto-created)']);
        }
        else
        {
            $primary_org = $member_orgs->first();
        }

        // Resolve credit orgs for multi-club scenarios.
        $credit_orgs = collect();
        $multi_club_target = match (true)
        {
            $triple_costume => 3,
            $dual_costume => 2,
            default => 0,
        };

        if ($multi_club_target > 0)
        {
            $seen_parent_ids = [];

            foreach ($member_orgs as $org)
            {
                $parent_id = $org->getPrimaryClub()->id;

                if (!in_array($parent_id, $seen_parent_ids, true))
                {
                    $credit_orgs->push($org);
                    $seen_parent_ids[] = $parent_id;

                    if ($credit_orgs->count() === $multi_club_target)
                    {
                        break;
                    }
                }
            }

            $flag_name = $triple_costume ? '--triple-costume' : '--dual-costume';

            if ($credit_orgs->count() < $multi_club_target)
            {
                $this->error("{$flag_name} requires the trooper to be a member of at least {$multi_club_target} clubs with different top-level parent organizations.");
                $this->error("Found {$member_orgs->count()} active membership(s) but could not resolve {$multi_club_target} distinct parent clubs.");

                return self::FAILURE;
            }
        }

        $costume = null;

        if ($multi_club_target > 0)
        {
            $costume = $this->resolveMultiClubCostume($trooper, $credit_orgs);
        }

        // Select costume randomly from the trooper's approved costumes, fall back to Handler.
        $costume ??= Costume::whereHas('organization_costumes.trooper_costumes', fn ($q) => $q->where('trooper_id', $trooper->id)
        )->inRandomOrder()->first();

        if ($costume === null)
        {
            $costume = Costume::where(Costume::NAME, Costume::HANDLER)->first();
            $this->warn("No approved costumes found for {$trooper->display_name} — using Handler as fallback.");
        }

        if ($costume === null)
        {
            $this->warn('No Handler costume exists — proceeding with no costume.');
        }

        // --no-eligible-orgs requires a non-handler costume so costume_organization_ids is respected.
        if ($no_eligible && $costume?->countsAsHandler())
        {
            $costume = Costume::whereNotIn(Costume::NAME, [Costume::HANDLER, Costume::COMMAND_STAFF])
                ->inRandomOrder()
                ->first();

            if ($costume === null)
            {
                $this->error('--no-eligible-orgs requires a non-handler costume, but none exist in the database.');

                return self::FAILURE;
            }

            $this->warn("Switched to non-handler costume \"{$costume->name}\" for --no-eligible-orgs scenario.");
        }

        // Build the event with the chosen timing scenario.
        $event_end = $expired
            ? Carbon::now()->subDays(45)
            : Carbon::now()->subDay();

        $event = Event::factory()
            ->withOrganization($primary_org)
            ->asClosed()
            ->withEventEnd($event_end)
            ->withEventStart($event_end->clone()->subHours(4))
            ->create();

        $this->ensureEventOrganizationCanAttend($event, $primary_org);

        foreach ($credit_orgs as $credit_org)
        {
            $this->ensureEventOrganizationCanAttend($event, $credit_org);
        }

        $event_shift = EventShift::factory()->forEvent($event)->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => $event_end,
            EventShift::SHIFT_STARTS_AT => $event_end->clone()->subHours(4),
        ]);

        $initial_credit_org_ids = $multi_club_target > 0
            ? $credit_orgs->pluck('id')->values()->all()
            : $this->creditOrgIdsForCostume($trooper, $costume);

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([
                EventTrooper::COSTUME_ID => $costume?->id,
                EventTrooper::COSTUME_ORGANIZATION_IDS => !empty($initial_credit_org_ids) ? $initial_credit_org_ids : null,
                EventTrooper::IS_HANDLER => $costume?->countsAsHandler() ?? false,
            ]);

        if ($no_eligible)
        {
            // Point costume_organization_ids at an org the trooper is not a member of.
            // getEligibleCreditOrganizations() will intersect with member assignments and return empty.
            $decoy_org = Organization::factory()->create(['name' => 'Decoy Org (no-eligible-orgs)']);

            DB::table('tt_event_troopers')
                ->where('id', $event_trooper->id)
                ->update(['costume_organization_ids' => json_encode([$decoy_org->id])]);

            $event_trooper->refresh();
        }

        $attended_token = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);
        $unable_token = Crypt::encryptString(EventTrooperStatus::UNABLE_TO_ATTEND->value);

        $attended_url = route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $attended_token,
        ]);

        $unable_url = route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $unable_token,
        ]);

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Scenario ──────────────────────────────────────</>');
        $this->info("  Trooper:       {$trooper->display_name} (ID {$trooper->id})");
        $this->info("  Event:         {$event->name} (ID {$event->id})");
        $this->info("  Shift:         {$event_shift->time_display}");
        $this->info("  Event end:     {$event_end->format('Y-m-d H:i')} ({$this->windowLabel($expired)})");
        $this->info('  Updates open:  '.($event->can_update_trooper_status ? '<fg=green>yes</>' : '<fg=red>no</>'));
        $this->info('  Costume:       '.($costume ? "{$costume->name} (ID {$costume->id})" : 'none'));
        $this->info('  Credit orgs:   '.($event_trooper->costume_organization_ids ? implode(', ', $event_trooper->costume_organization_ids) : 'none'));

        $this->newLine();
        $parent_orgs = $event_trooper->getEligibleCreditParentOrganizations();

        if ($parent_orgs->count() > 1)
        {
            $this->line('<fg=cyan;options=bold>── Club selection will be shown ───────────────────</>');
            foreach ($parent_orgs as $org)
            {
                $this->info("  [{$org->id}] {$org->name}");
            }
        }
        elseif ($parent_orgs->count() === 1)
        {
            $this->info('  Eligible clubs: '.$parent_orgs->first()->name.' (single — no selection prompt)');
        }
        else
        {
            $this->info('  Eligible clubs: none (default credit logic applies)');
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Confirmation URLs ──────────────────────────────</>');
        $this->info('  Attended:');
        $this->line("  {$attended_url}");
        $this->newLine();
        $this->info('  Unable to attend:');
        $this->line("  {$unable_url}");
        $this->newLine();

        return self::SUCCESS;
    }

    private function windowLabel(bool $expired): string
    {
        return $expired ? 'outside 30-day window' : 'within 30-day window';
    }

    private function resolveMultiClubCostume(Trooper $trooper, Collection $credit_orgs): Costume
    {
        $costume = Costume::query()
            ->whereNotIn(Costume::NAME, [Costume::HANDLER, Costume::COMMAND_STAFF])
            ->where(function ($query) use ($trooper, $credit_orgs) {
                foreach ($credit_orgs as $org)
                {
                    $query->whereHas('organization_costumes', function ($query) use ($trooper, $org) {
                        $query->where(OrganizationCostume::ORGANIZATION_ID, $org->id)
                            ->whereHas('trooper_costumes', fn ($query) => $query->where(TrooperCostume::TROOPER_ID, $trooper->id));
                    });
                }
            })
            ->first();

        $costume ??= Costume::firstOrCreate([
            Costume::NAME => 'Troop Credit Simulator Multi-Club Costume',
        ]);

        foreach ($credit_orgs as $org)
        {
            $organization_costume = OrganizationCostume::firstOrCreate([
                OrganizationCostume::ORGANIZATION_ID => $org->id,
                OrganizationCostume::COSTUME_ID => $costume->id,
            ]);

            TrooperCostume::firstOrCreate([
                TrooperCostume::TROOPER_ID => $trooper->id,
                TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
            ]);
        }

        return $costume;
    }

    private function ensureEventOrganizationCanAttend(Event $event, Organization $organization): void
    {
        EventOrganization::updateOrCreate(
            [
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
            ],
            [
                EventOrganization::CAN_ATTEND => true,
            ]
        );
    }

    /** @return array<int> */
    private function creditOrgIdsForCostume(Trooper $trooper, ?Costume $costume): array
    {
        if ($costume === null)
        {
            return [];
        }

        if ($costume->countsAsHandler())
        {
            return TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->pluck(TrooperAssignment::ORGANIZATION_ID)
                ->toArray();
        }

        return OrganizationCostume::query()
            ->where(OrganizationCostume::COSTUME_ID, $costume->id)
            ->whereHas('trooper_costumes', fn ($query) => $query->where(TrooperCostume::TROOPER_ID, $trooper->id))
            ->pluck(OrganizationCostume::ORGANIZATION_ID)
            ->toArray();
    }
}
