<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Messages\Events\Queries\GetEventsForDisplay;
use App\Messages\Events\Queries\GetHolidays;
use App\Messages\Events\Queries\GetTrooperSignUpStatuses;
use App\Messages\Events\Resources\CalendarEventResource;
use App\Messages\Events\Resources\CalendarMonthResource;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Messages\Organizations\Resources\HostingOrganizationOptions;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The next twelve months of upcoming events, laid out as month grids with holidays.
 *
 * @method static array call(Actor $actor)
 */
final class CalendarPageData extends Message
{
    private const MONTHS = 12;

    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    public function handle(): array
    {
        $first_month = now()->startOfMonth();
        $months = collect(range(0, self::MONTHS - 1))
            ->map(fn (int $i) => $first_month->copy()->addMonths($i));

        return [
            'months' => $months->map(fn (Carbon $month) => new CalendarMonthResource($month))->all(),
            'weekdays' => $this->getWeekdays(),
            'today' => now()->toDateString(),
            'events' => $this->getEvents(),
            'holidays' => (object) GetHolidays::call(
                from: $first_month->copy()->startOfWeek(CarbonInterface::SUNDAY),
                to: $months->last()->copy()->endOfMonth()->endOfWeek(CarbonInterface::SATURDAY),
            ),
            'hosting_organizations' => new HostingOrganizationOptions(
                GetOrganizationsForPicker::call(trooper: $this->actor, data: []),
            ),
            'has_map' => (bool) config('services.google.maps_api_key'),
        ];
    }

    /**
     * @return Collection<int, CalendarEventResource>
     */
    private function getEvents(): Collection
    {
        $events = GetEventsForDisplay::call();

        $my_statuses = GetTrooperSignUpStatuses::call(
            trooper: $this->actor,
            event_shift_ids: $events
                ->flatMap(fn (Event $event) => $event->event_shifts->pluck(EventShift::ID))
                ->all(),
        );

        return $events
            ->map(fn (Event $event) => new CalendarEventResource($event, $my_statuses))
            ->values();
    }

    /**
     * @return list<string> Short day names, Sunday first, in the trooper's language
     */
    private function getWeekdays(): array
    {
        $sunday = now()->startOfWeek(CarbonInterface::SUNDAY);

        return collect(range(0, 6))
            ->map(fn (int $i) => $sunday->copy()->addDays($i)->localFormat('D'))
            ->all();
    }
}
