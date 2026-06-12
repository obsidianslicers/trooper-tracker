<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $event_charity_columns = [
            'charity_direct_funds',
            'charity_indirect_funds',
            'charity_name',
            'charity_hours',
            'charity_notes',
        ];

        if (!$this->hasColumns('tt_events', $event_charity_columns))
        {
            return;
        }

        $this->copyEventCharityToFirstShift();

        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->dropColumn([
                'charity_direct_funds',
                'charity_indirect_funds',
                'charity_name',
                'charity_hours',
                'charity_notes',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->integer('charity_direct_funds')->default(0);
            $table->integer('charity_indirect_funds')->default(0);
            $table->string('charity_name', 128)->nullable();
            $table->integer('charity_hours')->nullable();
            $table->text('charity_notes')->nullable();
        });

        $this->copyShiftCharityToEvents();
    }

    private function copyEventCharityToFirstShift(): void
    {
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
                continue;
            }

            $first_shift_id = DB::table('tt_event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('deleted_at')
                ->orderBy('shift_starts_at')
                ->value('id');

            if ($first_shift_id === null)
            {
                continue;
            }

            DB::table('tt_event_shifts')
                ->where('id', $first_shift_id)
                ->update([
                    'charity_direct_funds' => $event->charity_direct_funds,
                    'charity_indirect_funds' => $event->charity_indirect_funds,
                    'charity_name' => $this->decodeNullableText($event->charity_name),
                    'charity_hours' => $event->charity_hours,
                    'charity_notes' => $this->decodeNullableText($event->charity_notes),
                ]);
        }
    }

    private function copyShiftCharityToEvents(): void
    {
        $events = DB::table('tt_events')
            ->select('id')
            ->get();

        foreach ($events as $event)
        {
            $totals = DB::table('tt_event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(charity_direct_funds), 0) as charity_direct_funds')
                ->selectRaw('COALESCE(SUM(charity_indirect_funds), 0) as charity_indirect_funds')
                ->selectRaw('SUM(charity_hours) as charity_hours')
                ->first();

            $charity_name = DB::table('tt_event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('deleted_at')
                ->whereNotNull('charity_name')
                ->where('charity_name', '!=', '')
                ->orderBy('shift_starts_at')
                ->value('charity_name');

            $charity_notes = DB::table('tt_event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('deleted_at')
                ->whereNotNull('charity_notes')
                ->where('charity_notes', '!=', '')
                ->orderBy('shift_starts_at')
                ->value('charity_notes');

            DB::table('tt_events')
                ->where('id', $event->id)
                ->update([
                    'charity_direct_funds' => $totals->charity_direct_funds ?? 0,
                    'charity_indirect_funds' => $totals->charity_indirect_funds ?? 0,
                    'charity_name' => $charity_name,
                    'charity_hours' => $totals->charity_hours,
                    'charity_notes' => $charity_notes,
                ]);
        }
    }

    private function decodeNullableText(?string $value): ?string
    {
        if ($value === null || $value === '')
        {
            return null;
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column)
        {
            if (!Schema::hasColumn($table, $column))
            {
                return false;
            }
        }

        return true;
    }
};
