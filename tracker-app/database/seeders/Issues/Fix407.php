<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Mail\Fix407OutstandingCredit;
use App\Models\EventTrooper;
use Database\Seeders\Issues\Concerns\ReportsToAdministrators;
use Database\Seeders\Issues\Support\HistoricalCreditResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Makes every attended TT1.0 signup's credit match the club its legacy signup recorded — TT1.0
 * data is authoritative for TT1.0 signups, whatever the shift date. Unmappable signups keep their
 * credit and are reported. See docs/ISSUE_MIGRATIONS.md.
 */
class Fix407 extends Seeder
{
    use ReportsToAdministrators;

    private HistoricalCreditResolver $resolver;

    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<int, array<string, mixed>> */
    private array $outstanding_rows = [];

    public function run(MagicBus $bus): void
    {
        $this->counts = array_fill_keys(
            ['tt1_rows', 'already_correct', 'corrected', 'outstanding'],
            0,
        );
        $this->outstanding_rows = [];

        DB::transaction(function (): void
        {
            $this->resolver = HistoricalCreditResolver::load();

            if (!$this->resolver->legacy()->isAvailable())
            {
                $this->command?->warn('Fix407: legacy TT1.0 tables not found; nothing to do.');

                return;
            }

            $this->reconcileTt1Rows();
            $this->printSummary();
        });

        $this->emailAdministrators($bus, Fix407OutstandingCredit::class, $this->outstanding_rows);
    }

    private function printSummary(): void
    {
        $this->printCounts('Fix407 complete:', [
            'tt1_rows' => 'TT1.0 signups checked',
            'already_correct' => 'Already matching legacy signup',
            'corrected' => 'Corrected to legacy signup',
            'outstanding' => 'Outstanding (admin review)',
        ], $this->counts);
    }

    private function reconcileTt1Rows(): void
    {
        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->with(['trooper', 'costume', 'event_shift.event'])
            ->chunkById(500, function ($event_troopers): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    if ($this->resolver->isTt1Signup($event_trooper))
                    {
                        $this->counts['tt1_rows']++;
                        $this->reconcileRow($event_trooper);
                    }
                }
            });
    }

    private function reconcileRow(EventTrooper $event_trooper): void
    {
        $legacy = $this->resolver->legacyCredit($event_trooper);
        $current_ids = $event_trooper->creditedOrgIds();

        if (!$legacy->isResolved())
        {
            // uncredited rows are Fix406's to report
            if (!empty($current_ids))
            {
                $this->counts['outstanding']++;
                $reason = $legacy->note.' Existing credit left unchanged.';
                $this->outstanding_rows[] = $this->reportRow($event_trooper, $reason);
            }

            return;
        }

        $this->applyLegacyCredit($event_trooper, $current_ids, $legacy->org_ids);
    }

    /**
     * @param  array<int, int>  $current_ids
     * @param  array<int, int>  $legacy_ids
     */
    private function applyLegacyCredit(
        EventTrooper $event_trooper,
        array $current_ids,
        array $legacy_ids,
    ): void {
        $target_ids = $this->resolver->mergeKeepingSpecificity($current_ids, $legacy_ids);
        $stored_ids = $event_trooper->costume_organization_ids ?? [];

        if (HistoricalCreditResolver::sameIds($target_ids, $stored_ids))
        {
            $this->counts['already_correct']++;

            return;
        }

        $event_trooper->costume_organization_ids = $target_ids;
        $event_trooper->saveQuietly();
        $this->counts['corrected']++;
    }
}
