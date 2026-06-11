<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-time correction seeder. Run manually after the add_charity_to_event_shifts migration.
 *
 * Pass 1 — Re-maps per-shift charity from the legacy v1.0 `events` table directly onto
 * `tt_event_shifts` rows. This recovers data that was silently dropped during the original
 * 1.0→2.0 migration because EventSeeder only copied charity from the parent event row.
 * Requires the legacy `events` table to still be present in the database.
 *
 * Pass 2 — For any shift that still has no charity after Pass 1 (e.g. the legacy row was
 * not found or had no data), copies the parent event's current `tt_events.charity_*` data
 * to the first shift of that event. This preserves any charity entered natively in v2.0
 * before this migration runs.
 */
class EventCharityShiftSeeder extends Seeder
{
    public function run(): void
    {
        $pass_one = $this->passOne();
        $pass_two = $this->passTwo();

        $this->printSummary($pass_one, $pass_two);
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
            'updated_with_empty_charity_data' => 0,
        ];

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

        $shifts = DB::table('tt_event_shifts')
            ->whereNull('deleted_at')
            ->select(['id'])
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

            $charity_data = [
                'charity_direct_funds'   => $legacy->charityDirectFunds ?? 0,
                'charity_indirect_funds' => $legacy->charityIndirectFunds ?? 0,
                'charity_name'           => $this->decodeNullableText($legacy->charityName),
                'charity_hours'          => $legacy->charityAddHours,
                'charity_notes'          => $this->decodeNullableText($legacy->charityNote),
            ];

            DB::table('tt_event_shifts')
                ->where('id', $shift->id)
                ->update($charity_data);

            $stats['shifts_updated']++;

            if ($this->hasCharityData((object) $charity_data))
            {
                $stats['updated_with_charity_data']++;
            }
            else
            {
                $stats['updated_with_empty_charity_data']++;
            }
        }

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    private function passTwo(): array
    {
        $stats = [
            'events_with_event_level_charity_scanned' => 0,
            'skipped_no_active_shifts' => 0,
            'skipped_shift_charity_exists' => 0,
            'fallback_updates_applied' => 0,
        ];

        // For each event that still has event-level charity data, copy it down only
        // when none of the event's non-deleted shifts already has charity data.
        $events_with_charity = DB::table('tt_events')
            ->where(function ($q) {
                $q->where('charity_direct_funds', '>', 0)
                    ->orWhere('charity_indirect_funds', '>', 0)
                    ->orWhere(fn ($q) => $q->whereNotNull('charity_name')->where('charity_name', '!=', ''))
                    ->orWhereNotNull('charity_hours')
                    ->orWhere(fn ($q) => $q->whereNotNull('charity_notes')->where('charity_notes', '!=', ''));
            })
            ->select([
                'id',
                'charity_direct_funds',
                'charity_indirect_funds',
                'charity_name',
                'charity_hours',
                'charity_notes',
            ])
            ->get();

        foreach ($events_with_charity as $event)
        {
            $stats['events_with_event_level_charity_scanned']++;

            $first_shift_id = DB::table('tt_event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('deleted_at')
                ->orderBy('shift_starts_at')
                ->value('id');

            if ($first_shift_id === null)
            {
                $stats['skipped_no_active_shifts']++;

                continue;
            }

            $shift_has_charity = DB::table('tt_event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('charity_direct_funds', '>', 0)
                        ->orWhere('charity_indirect_funds', '>', 0)
                        ->orWhere(fn ($q) => $q->whereNotNull('charity_name')->where('charity_name', '!=', ''))
                        ->orWhereNotNull('charity_hours')
                        ->orWhere(fn ($q) => $q->whereNotNull('charity_notes')->where('charity_notes', '!=', ''));
                })
                ->exists();

            if ($shift_has_charity)
            {
                $stats['skipped_shift_charity_exists']++;

                continue;
            }

            DB::table('tt_event_shifts')
                ->where('id', $first_shift_id)
                ->update([
                    'charity_direct_funds'   => $event->charity_direct_funds,
                    'charity_indirect_funds' => $event->charity_indirect_funds,
                    'charity_name'           => $this->decodeNullableText($event->charity_name),
                    'charity_hours'          => $event->charity_hours,
                    'charity_notes'          => $this->decodeNullableText($event->charity_notes),
                ]);

            $stats['fallback_updates_applied']++;
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
     * @param  array<string, int>  $pass_two
     */
    private function printSummary(array $pass_one, array $pass_two): void
    {
        $this->command?->info('Event charity shift backfill');
        $this->command?->newLine();

        $this->command?->line('Pass 1: legacy shift charity mapping');
        $this->command?->line('- Shifts scanned: '.$this->formatNumber($pass_one['shifts_scanned']));
        $this->command?->line('- Legacy rows matched by shift id: '.$this->formatNumber($pass_one['legacy_rows_matched']));
        $this->command?->line('- Shifts updated: '.$this->formatNumber($pass_one['shifts_updated']));
        $this->command?->line('- Updated with charity data: '.$this->formatNumber($pass_one['updated_with_charity_data']));
        $this->command?->line('- Updated with empty charity data: '.$this->formatNumber($pass_one['updated_with_empty_charity_data']));
        $this->command?->line('- Skipped, no legacy row: '.$this->formatNumber($pass_one['skipped_no_legacy_row']));
        $this->command?->newLine();

        $this->command?->line('Pass 2: event-level fallback');
        $this->command?->line('- Events with event-level charity scanned: '.$this->formatNumber($pass_two['events_with_event_level_charity_scanned']));
        $this->command?->line('- Fallback updates applied: '.$this->formatNumber($pass_two['fallback_updates_applied']));
        $this->command?->line('- Skipped, shift charity already exists: '.$this->formatNumber($pass_two['skipped_shift_charity_exists']));
        $this->command?->line('- Skipped, no active shifts: '.$this->formatNumber($pass_two['skipped_no_active_shifts']));
        $this->command?->newLine();

        $this->command?->line('Done.');
    }

    private function formatNumber(int $value): string
    {
        return number_format($value);
    }
}
