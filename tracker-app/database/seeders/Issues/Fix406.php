<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Mail\Fix406OutstandingCredit;
use App\Models\EventTrooper;
use Database\Seeders\Issues\Concerns\ReportsToAdministrators;
use Database\Seeders\Issues\Support\HistoricalCreditResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Restores credit on attended rows left with none — wiped by the pre-#262 roster save or never
 * set by the import for handlers. event_trooper doesn't audit credit, so it's re-derived by
 * HistoricalCreditResolver; anything it can't settle is reported. See docs/ISSUE_MIGRATIONS.md.
 */
class Fix406 extends Seeder
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
            ['scanned', 'resolved_tt1', 'resolved_tt2', 'outstanding'],
            0,
        );
        $this->outstanding_rows = [];

        DB::transaction(function (): void
        {
            $this->resolver = HistoricalCreditResolver::load();
            $this->warnIfLegacyUnavailable();

            $this->restoreUncreditedRows();
            $this->printSummary();
        });

        $this->emailAdministrators($bus, Fix406OutstandingCredit::class, $this->outstanding_rows);
    }

    private function printSummary(): void
    {
        $this->printCounts('Fix406 complete:', [
            'scanned' => 'Scanned (no credit)',
            'resolved_tt1' => 'Restored from TT1.0 signup',
            'resolved_tt2' => 'Restored from TT2.0 history',
            'outstanding' => 'Outstanding (admin review)',
        ], $this->counts);
    }

    private function restoreUncreditedRows(): void
    {
        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereNull(EventTrooper::ORGANIZATION_ID)
            ->where(function ($query): void
            {
                // whereJsonLength, not orWhere('[]') — MySQL never does JSON-aware equality
                // against a bound parameter, so the old orWhere('[]') matched nothing
                $query->whereNull(EventTrooper::COSTUME_ORGANIZATION_IDS)
                    ->orWhereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, 0);
            })
            ->with(['trooper', 'costume', 'event_shift.event'])
            // chunkById (not chunk): resolved rows stop matching the filter mid-iteration
            ->chunkById(200, function ($event_troopers): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    $this->restoreRow($event_trooper);
                }
            });
    }

    private function restoreRow(EventTrooper $event_trooper): void
    {
        $this->counts['scanned']++;

        $resolution = $this->resolver->expectedCredit($event_trooper);

        if (!$resolution->resolved)
        {
            $this->counts['outstanding']++;
            $this->outstanding_rows[] = $this->reportRow($event_trooper, $resolution->reason);

            return;
        }

        $event_trooper->costume_organization_ids = $resolution->org_ids;
        $event_trooper->saveQuietly();

        $origin = $this->resolver->isTt1Signup($event_trooper) ? 'resolved_tt1' : 'resolved_tt2';
        $this->counts[$origin]++;
    }

    private function warnIfLegacyUnavailable(): void
    {
        if (!$this->resolver->legacy()->isAvailable())
        {
            $this->command?->warn('Fix406: no legacy TT1.0 tables — treating every row as TT2.0.');
        }
    }
}
