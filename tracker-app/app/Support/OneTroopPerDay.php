<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * The optional "one troop per day" rule (tracker.count_as_one_troop_per_day).
 *
 * While it's on, a trooper who attends more than one shift of the same event on the same day
 * gets one troop for that day: the earliest of those shifts counts, the others don't. Hours
 * and charity still count for every shift. Shifts on other days and shifts of other events
 * are unaffected.
 *
 * "Same day" is the date of the shift's start as the tracker shows it (the app timezone,
 * which is also how it's stored).
 */
final class OneTroopPerDay
{
    /** Forum posts, Discord and the event page, for an event with more than one shift on a day. */
    public const NOTICE = 'One troop per day: more than one shift on the same day counts as one troop.';

    /** Beside a shift on the service record that didn't add a troop. */
    public const REPEAT_LABEL = 'Same day, counted once';

    public const REPEAT_HINT = 'You worked an earlier shift of this event on the same day, and that shift already counted as the troop for the day.';

    public static function isOn(): bool
    {
        return (bool) config('tracker.count_as_one_troop_per_day');
    }

    /**
     * The day a shift belongs to for this rule.
     */
    public static function dayOf(EventShift $shift): string
    {
        return $shift->shift_starts_at->toDateString();
    }

    /**
     * Leaves out sign-ups that don't add a troop: those with an earlier attended shift by the
     * same trooper, at the same event, on the same day. A no-op while the rule is off.
     *
     * @param  string  $table  The event troopers table as the query names it.
     */
    public static function excludeRepeats(Builder $query, string $table = 'tt_event_troopers'): void
    {
        if (!self::isOn())
        {
            return;
        }

        $query->whereNotExists(function ($sub) use ($table): void
        {
            $sub->selectRaw('1')
                ->from('tt_event_shifts as otpd_shift')
                ->join('tt_event_shifts as otpd_earlier_shift', function (JoinClause $join): void
                {
                    $join->on('otpd_earlier_shift.event_id', '=', 'otpd_shift.event_id')
                        ->whereRaw('DATE(otpd_earlier_shift.shift_starts_at) = DATE(otpd_shift.shift_starts_at)')
                        ->whereNull('otpd_earlier_shift.deleted_at');
                })
                ->join('tt_event_troopers as otpd_earlier', 'otpd_earlier.event_shift_id', '=', 'otpd_earlier_shift.id')
                ->whereColumn('otpd_shift.id', $table.'.event_shift_id')
                ->whereColumn('otpd_earlier.trooper_id', $table.'.trooper_id')
                ->where('otpd_earlier.status', EventTrooperStatus::ATTENDED->value)
                ->whereNull('otpd_earlier.deleted_at')
                ->where(function ($earlier) use ($table): void
                {
                    $earlier->whereColumn('otpd_earlier_shift.shift_starts_at', '<', 'otpd_shift.shift_starts_at')
                        ->orWhere(function ($same_start) use ($table): void
                        {
                            $same_start->whereColumn('otpd_earlier_shift.shift_starts_at', '=', 'otpd_shift.shift_starts_at')
                                ->whereColumn('otpd_earlier.id', '<', $table.'.id');
                        });
                });
        });
    }

    /**
     * The same rule for sign-ups already loaded: which attended ones don't add a troop.
     *
     * @param  iterable<EventTrooper>  $event_troopers  With event_shift loaded.
     * @return array<int, true> Keyed by event trooper id.
     */
    public static function repeatIds(iterable $event_troopers): array
    {
        if (!self::isOn())
        {
            return [];
        }

        $attended = collect($event_troopers)
            ->filter(fn (EventTrooper $et): bool => $et->status === EventTrooperStatus::ATTENDED
                && $et->event_shift !== null
                && $et->event_shift->shift_starts_at !== null)
            ->sortBy([
                fn (EventTrooper $a, EventTrooper $b): int => $a->event_shift->shift_starts_at <=> $b->event_shift->shift_starts_at,
                fn (EventTrooper $a, EventTrooper $b): int => $a->id <=> $b->id,
            ]);

        $seen = [];
        $repeats = [];

        foreach ($attended as $et)
        {
            $key = $et->trooper_id.'|'.$et->event_shift->event_id.'|'.self::dayOf($et->event_shift);

            if (isset($seen[$key]))
            {
                $repeats[$et->id] = true;
            }

            $seen[$key] = true;
        }

        return $repeats;
    }
}
