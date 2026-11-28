<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries\Corrections;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use Carbon\CarbonImmutable;
use Hyperdrive\Message;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Completed events a trooper might ask for credit for: those within a day of the date they
 * remember, or whose title or venue matches what they typed. A day either side covers
 * troops that ran past midnight and dates remembered a little off.
 *
 * @method static Collection<int, Event> call(?string $date = null, ?string $search = null)
 */
final class GetEventsForTroopCorrection extends Message
{
    public const MIN_SEARCH_LENGTH = 3;

    private const LIMIT = 20;

    public function __construct(
        private readonly ?string $date = null,
        private readonly ?string $search = null,
    ) {}

    /**
     * @return Collection<int, Event>
     */
    public function handle(): Collection
    {
        $day = $this->day();
        $search = trim((string) $this->search);

        if ($day === null && mb_strlen($search) < self::MIN_SEARCH_LENGTH)
        {
            return collect();
        }

        return Event::query()
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->when($day, fn ($query) => $query
                ->where(Event::EVENT_START, '<=', $day->addDay()->endOfDay())
                ->where(Event::EVENT_END, '>=', $day->subDay()->startOfDay()))
            ->when(mb_strlen($search) >= self::MIN_SEARCH_LENGTH, fn ($query) => $query
                ->where(fn ($match) => $match
                    ->where(Event::NAME, 'like', '%'.$search.'%')
                    ->orWhere(Event::VENUE, 'like', '%'.$search.'%')))
            ->with(['event_shifts' => fn ($query) => $query->orderBy(EventShift::SHIFT_STARTS_AT)])
            ->orderByDesc(Event::EVENT_START)
            ->limit(self::LIMIT)
            ->get();
    }

    private function day(): ?CarbonImmutable
    {
        if ($this->date === null || $this->date === '')
        {
            return null;
        }

        try
        {
            return CarbonImmutable::parse($this->date)->startOfDay();
        }
        catch (Throwable)
        {
            return null;
        }
    }
}
