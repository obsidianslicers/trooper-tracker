<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One month of the events calendar: its weeks, Sunday first, each day marked whether it falls in
 * the month (the days before and after fill out the first and last weeks).
 *
 * @property Carbon $resource The first day of the month
 */
class CalendarMonthResource extends JsonResource
{
    /**
     * @return array{key: string, label: string, weeks: list<list<array{date: string, day: string, weekday: string, in_month: bool}>>}
     */
    public function toArray(Request $request): array
    {
        $month = $this->resource;
        $date = $month->copy()->startOfWeek(CarbonInterface::SUNDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(CarbonInterface::SATURDAY);
        $weeks = [];

        for (; $date <= $end; $date->addDay())
        {
            if ($date->isSunday())
            {
                $weeks[] = [];
            }

            $weeks[count($weeks) - 1][] = [
                'date' => $date->toDateString(),
                'day' => $date->localFormat('j'),
                'weekday' => $date->localFormat('D'),
                'in_month' => $date->month === $month->month,
            ];
        }

        return ['key' => $month->format('Y-m'), 'label' => $month->localFormat('F Y'), 'weeks' => $weeks];
    }
}
