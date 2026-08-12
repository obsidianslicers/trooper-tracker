<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Fix406OutstandingCredit;
use App\Models\EventTrooper;
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

            EventTrooper::query()
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
                ->whereNull(EventTrooper::ORGANIZATION_ID)
                ->where(function ($query): void {
                    $query->whereNull(EventTrooper::COSTUME_ORGANIZATION_IDS)
                        ->orWhere(EventTrooper::COSTUME_ORGANIZATION_IDS, '[]');
                })
                ->with(['trooper.trooper_costumes.organization_costume', 'trooper.trooper_assignments', 'costume', 'event_shift.event'])
                ->chunk(200, function ($event_troopers) use (&$counts, &$outstanding_rows): void {
                    foreach ($event_troopers as $event_trooper)
                    {
                        $counts['scanned']++;

                        $eligible_parent_orgs = $event_trooper->getEligibleCreditParentOrganizations();

                        if ($eligible_parent_orgs->isEmpty())
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

                        $eligible_org_ids = $event_trooper->getEligibleCreditOrganizations()
                            ->pluck('id')
                            ->values()
                            ->all();

                        $event_trooper->costume_organization_ids = $eligible_org_ids;
                        $event_trooper->saveQuietly();

                        if ($eligible_parent_orgs->count() === 1)
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
