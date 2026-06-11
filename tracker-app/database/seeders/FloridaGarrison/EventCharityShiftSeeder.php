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
        $this->passOne();
        $this->passTwo();
    }

    private function passOne(): void
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

        $shifts = DB::table('tt_event_shifts')
            ->whereNull('deleted_at')
            ->select(['id'])
            ->get();

        foreach ($shifts as $shift)
        {
            $legacy = $legacy_rows->get($shift->id);

            if ($legacy === null)
            {
                continue;
            }

            DB::table('tt_event_shifts')
                ->where('id', $shift->id)
                ->update([
                    'charity_direct_funds'   => $legacy->charityDirectFunds ?? 0,
                    'charity_indirect_funds' => $legacy->charityIndirectFunds ?? 0,
                    'charity_name'           => $legacy->charityName ? html_entity_decode($legacy->charityName, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
                    'charity_hours'          => $legacy->charityAddHours,
                    'charity_notes'          => $legacy->charityNote ? html_entity_decode($legacy->charityNote, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
                ]);
        }
    }

    private function passTwo(): void
    {
        // For each event that still has charity data at the event level but whose first shift
        // has none, copy the event-level charity down to the first shift.
        $events_with_charity = DB::table('tt_events')
            ->where(function ($q) {
                $q->where('charity_direct_funds', '>', 0)
                    ->orWhere('charity_indirect_funds', '>', 0)
                    ->orWhereNotNull('charity_name');
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
            $first_shift_id = DB::table('tt_event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('deleted_at')
                ->orderBy('shift_starts_at')
                ->value('id');

            if ($first_shift_id === null)
            {
                continue;
            }

            $shift_has_charity = DB::table('tt_event_shifts')
                ->where('id', $first_shift_id)
                ->where(function ($q) {
                    $q->where('charity_direct_funds', '>', 0)
                        ->orWhere('charity_indirect_funds', '>', 0)
                        ->orWhereNotNull('charity_name');
                })
                ->exists();

            if ($shift_has_charity)
            {
                continue;
            }

            DB::table('tt_event_shifts')
                ->where('id', $first_shift_id)
                ->update([
                    'charity_direct_funds'   => $event->charity_direct_funds,
                    'charity_indirect_funds' => $event->charity_indirect_funds,
                    'charity_name'           => $event->charity_name ? html_entity_decode($event->charity_name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
                    'charity_hours'          => $event->charity_hours,
                    'charity_notes'          => $event->charity_notes ? html_entity_decode($event->charity_notes, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
                ]);
        }
    }
}
