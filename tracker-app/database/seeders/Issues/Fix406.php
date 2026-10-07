<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Fix406OutstandingCredit;
use App\Models\EventTrooper;
use App\Models\Organization;
use Database\Seeders\Issues\Concerns\ExcludesPrematureCredit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Backfills credit for event_trooper records left with no credit source at all.
 *
 * Before the admin roster-update controller was fixed (#262), every save unconditionally
 * cleared both costume_organization_ids and organization_id whenever org-selection data
 * wasn't submitted for a row, silently destroying any existing credit. event_trooper only
 * audits the status column, so the original values cannot be recovered — this seeder instead
 * re-derives credit from current costume approvals / membership via
 * EventTrooper::getEligibleCreditOrganizations(), the same resolver the self-service
 * attendance flow uses.
 *
 * Three outcomes per affected record:
 *   - Exactly one eligible top-level club → unambiguous, populate costume_organization_ids
 *     with that club's eligible org IDs.
 *   - More than one eligible top-level club → ambiguous (the self-service flow would have
 *     asked the trooper to choose); rather than guess, credit all eligible clubs and track
 *     these separately so they can be audited afterward.
 *   - No eligible club → cannot determine, skipped and requires manual review. These are
 *     emailed to administrators via Fix406OutstandingCredit once the run completes.
 */
class Fix406 extends Seeder
{
    use ExcludesPrematureCredit;

    public function run(MagicBus $bus): void
    {
        $outstanding_rows = [];

        DB::transaction(function () use (&$outstanding_rows): void {
            $counts = [
                'scanned' => 0,
                'resolved_single_club' => 0,
                'resolved_multi_club' => 0,
                'skipped_no_eligible_org' => 0,
            ];

            $all_orgs = Organization::all([Organization::ID, Organization::NODE_PATH])->keyBy(Organization::ID);
            $join_signals = $this->buildJoinSignals($all_orgs);

            EventTrooper::query()
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
                ->whereNull(EventTrooper::ORGANIZATION_ID)
                ->where(function ($query): void {
                    // whereJsonLength, not orWhere('[]') — MySQL never does JSON-aware equality
                    // against a bound parameter, so the old orWhere('[]') matched nothing
                    $query->whereNull(EventTrooper::COSTUME_ORGANIZATION_IDS)
                        ->orWhereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, 0);
                })
                ->with(['trooper.trooper_costumes.organization_costume', 'trooper.trooper_assignments', 'costume', 'event_shift.event'])
                // chunkById (not chunk) because resolved rows are saved with a non-null
                // costume_organization_ids inside the loop, which is also the column being
                // filtered on above — chunk()'s offset-based paging would skip unprocessed
                // rows as the matching result set shrinks mid-iteration.
                ->chunkById(200, function ($event_troopers) use (&$counts, &$outstanding_rows, $all_orgs, $join_signals): void {
                    foreach ($event_troopers as $event_trooper)
                    {
                        $counts['scanned']++;

                        $shift_date = $event_trooper->event_shift?->shift_starts_at;
                        $eligible_org_ids = $event_trooper->getEligibleCreditOrganizations()
                            ->pluck('id')
                            ->values()
                            ->all();

                        if ($shift_date !== null)
                        {
                            // Current eligibility reflects today's membership, not membership at
                            // the time of the shift — never credit a club the trooper joined
                            // after this shift happened.
                            $eligible_org_ids = $this->excludePremature(
                                $eligible_org_ids,
                                $event_trooper->trooper_id,
                                $shift_date,
                                $all_orgs,
                                $join_signals,
                            );
                        }

                        if (empty($eligible_org_ids))
                        {
                            $counts['skipped_no_eligible_org']++;
                            $outstanding_rows[] = [
                                'event_trooper_id' => $event_trooper->id,
                                'trooper_name' => $event_trooper->trooper->display_name,
                                'event_name' => $event_trooper->event_shift->event->name,
                                'event_id' => $event_trooper->event_shift->event->id,
                                'costume_name' => $event_trooper->costume?->name,
                            ];

                            continue;
                        }

                        $event_trooper->costume_organization_ids = $eligible_org_ids;
                        $event_trooper->saveQuietly();

                        $distinct_parent_count = collect($eligible_org_ids)
                            ->map(fn (int $id) => Organization::rootIdFromPath($all_orgs->get($id)->node_path))
                            ->unique()
                            ->count();

                        if ($distinct_parent_count === 1)
                        {
                            $counts['resolved_single_club']++;
                        }
                        else
                        {
                            $counts['resolved_multi_club']++;
                        }
                    }
                });

            $this->command?->info('Fix406 complete:');
            $this->command?->info("  Scanned:                       {$counts['scanned']}");
            $this->command?->info("  Resolved (single club):        {$counts['resolved_single_club']}");
            $this->command?->info("  Resolved (multiple clubs):     {$counts['resolved_multi_club']}");
            $this->command?->info("  Skipped (no eligible org):     {$counts['skipped_no_eligible_org']}");
        });

        $this->emailOutstandingRowsToAdministrators($bus, $outstanding_rows);
    }

    private function emailOutstandingRowsToAdministrators(MagicBus $bus, array $outstanding_rows): void
    {
        if (empty($outstanding_rows))
        {
            return;
        }

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            Mail::to($admin->email)->queue(new Fix406OutstandingCredit($admin, $outstanding_rows));
        }
    }
}
