<?php

declare(strict_types=1);

namespace App\Messages\Concerns;

use App\Models\EventShift;
use Illuminate\Support\Collection;

/**
 * What a set of shifts raised and gave, formatted for the Donation Event Summary.
 */
trait SumsDonationAmounts
{
    use FormatsReportNumbers;

    /**
     * @param  Collection<int, EventShift>  $shifts
     * @return array<string, string>
     */
    protected function amounts(Collection $shifts, bool $cents): array
    {
        $direct = $shifts->sum('charity_direct_funds');
        $indirect = $shifts->sum('charity_indirect_funds');
        $money = fn ($value): string => $cents ? $this->funds($value) : $this->count($value);

        return [
            'direct' => $money($direct),
            'indirect' => $money($indirect),
            'total' => $money($direct + $indirect),
            'hours' => $this->count($shifts->sum(fn (EventShift $s) => $s->effective_charity_hours)),
            'attendees' => $this->count($shifts->sum('attendees_count')),
            'trooper_hours' => $this->count($shifts->sum(
                fn (EventShift $s) => $s->effective_charity_hours * $s->attendees_count
            )),
        ];
    }
}
