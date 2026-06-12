<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optional Florida Garrison legacy import correction.
 *
 * Re-maps per-shift charity from the legacy v1.0 `events` table directly onto
 * `tt_event_shifts` rows. This recovers data that was silently dropped during the original
 * 1.0→2.0 migration because EventSeeder only copied charity from the parent event row.
 * Requires the legacy `events` table to still be present in the database.
 */
class EventCharityShiftSeeder extends Seeder
{
    public function run(): void
    {
        $pass_one = $this->passOne();

        $this->printSummary($pass_one);
    }

    /**
     * @return array<string, int>
     */
    private function passOne(): array
    {
        $stats = [
            'shifts_scanned' => 0,
            'legacy_rows_matched' => 0,
            'shifts_updated' => 0,
            'skipped_no_legacy_row' => 0,
            'updated_with_charity_data' => 0,
            'skipped_empty_legacy_charity' => 0,
            'skipped_legacy_table_unavailable' => 0,
        ];

        $legacy_rows = collect();

        if (!Schema::hasTable('events'))
        {
            $stats['skipped_legacy_table_unavailable'] = 1;
        }
        else
        {
            $legacy_rows = DB::table('events')
                ->select([
                    'id',
                    'charityDirectFunds',
                    'charityIndirectFunds',
                    'charityName',
                    'charityAddHours',
                    'charityNote',
                ])
                ->get()
                ->keyBy('id');
        }

        $shifts = DB::table('tt_event_shifts')
            ->whereNull('deleted_at')
            ->select(['id', 'shift_starts_at', 'shift_ends_at'])
            ->get();

        foreach ($shifts as $shift)
        {
            $stats['shifts_scanned']++;

            $legacy = $legacy_rows->get($shift->id);

            if ($legacy === null)
            {
                $stats['skipped_no_legacy_row']++;

                continue;
            }

            $stats['legacy_rows_matched']++;

            $offset = (int) ($legacy->charityAddHours ?? 0);
            $duration = (int) \Carbon\Carbon::parse($shift->shift_starts_at)->diffInHours(\Carbon\Carbon::parse($shift->shift_ends_at));

            $charity_data = [
                'charity_direct_funds'   => $legacy->charityDirectFunds ?? 0,
                'charity_indirect_funds' => $legacy->charityIndirectFunds ?? 0,
                'charity_name'           => $this->decodeNullableText($legacy->charityName),
                'charity_hours'          => $offset === 0 ? null : $duration + $offset,
                'charity_notes'          => $this->decodeNullableText($legacy->charityNote),
            ];

            if (!$this->hasCharityData((object) $charity_data))
            {
                $stats['skipped_empty_legacy_charity']++;

                continue;
            }

            DB::table('tt_event_shifts')
                ->where('id', $shift->id)
                ->update($charity_data);

            $stats['shifts_updated']++;
            $stats['updated_with_charity_data']++;
        }

        return $stats;
    }

    private function decodeNullableText(?string $value): ?string
    {
        if ($value === null || $value === '')
        {
            return null;
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function hasCharityData(object $row): bool
    {
        return ($row->charity_direct_funds ?? 0) > 0
            || ($row->charity_indirect_funds ?? 0) > 0
            || ($row->charity_name ?? null) !== null
            || ($row->charity_hours ?? null) !== null
            || ($row->charity_notes ?? null) !== null;
    }

    /**
     * @param  array<string, int>  $pass_one
     */
    private function printSummary(array $pass_one): void
    {
        $this->command?->info('Florida Garrison event charity shift backfill');
        $this->command?->newLine();

        $this->command?->line('Legacy shift charity mapping');
        $this->command?->line('- Shifts scanned: '.$this->formatNumber($pass_one['shifts_scanned']));
        $this->command?->line('- Legacy rows matched by shift id: '.$this->formatNumber($pass_one['legacy_rows_matched']));
        $this->command?->line('- Shifts updated: '.$this->formatNumber($pass_one['shifts_updated']));
        $this->command?->line('- Updated with charity data: '.$this->formatNumber($pass_one['updated_with_charity_data']));
        $this->command?->line('- Skipped, empty legacy charity data: '.$this->formatNumber($pass_one['skipped_empty_legacy_charity']));
        $this->command?->line('- Skipped, no legacy row: '.$this->formatNumber($pass_one['skipped_no_legacy_row']));
        $this->command?->line('- Skipped, legacy table unavailable: '.$this->formatNumber($pass_one['skipped_legacy_table_unavailable']));
        $this->command?->newLine();

        $this->command?->line('Done.');
    }

    private function formatNumber(int $value): string
    {
        return number_format($value);
    }
}
