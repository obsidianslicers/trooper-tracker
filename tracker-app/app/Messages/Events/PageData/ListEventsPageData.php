<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Facades\TroopTrackerFacade as TroopTracker;
use App\Messages\Events\Queries\GetEventsForDisplay;
use App\Messages\Events\Queries\GetTrooperSignUpStatuses;
use App\Messages\Events\Resources\UpcomingEventResource;
use App\Messages\Notices\Queries\GetFirstUnreadNotice;
use App\Messages\Notices\Queries\GetUnreadNoticeCount;
use App\Messages\Notices\Resources\UnreadNoticeResource;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Messages\Organizations\Resources\HostingOrganizationOptions;
use App\Messages\Support\Queries\GetSupportGoal;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Closure;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Everything the upcoming events list shows: the events with their shifts and who is going,
 * the trooper's own sign-ups, the filter choices, and any unread notice.
 *
 * @method static array call(Actor $actor)
 */
final class ListEventsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    public function handle(): array
    {
        return [
            'events' => $this->getEvents(),
            'hosting_organizations' => new HostingOrganizationOptions(
                GetOrganizationsForPicker::call(trooper: $this->actor, data: []),
            ),
            'notices' => $this->getNotices(),
            'has_map' => (bool) config('services.google.maps_api_key'),
            'support' => $this->getSupport(),
        ];
    }

    /**
     * The support goal is slow to work out, so it comes as a closure the page can load later.
     */
    private function getSupport(): ?Closure
    {
        return TroopTracker::isSupportGoalEnabled()
            ? fn (): array => GetSupportGoal::call()
            : null;
    }

    /**
     * @return Collection<int, UpcomingEventResource>
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
            ->map(fn (Event $event) => new UpcomingEventResource($event, $my_statuses))
            ->values();
    }

    private function getNotices(): array
    {
        $count = GetUnreadNoticeCount::call(trooper: $this->actor);

        $notice = $count === 1 ? GetFirstUnreadNotice::call(trooper: $this->actor) : null;

        return [
            'count' => $count,
            'notice' => $notice ? new UnreadNoticeResource($notice) : null,
        ];
    }
}
