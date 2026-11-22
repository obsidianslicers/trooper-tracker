<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Messages\Events\Resources\MapEventResource;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Messages\Organizations\Resources\HostingOrganizationOptions;
use App\Models\Event;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Upcoming events that have a location, as pins on the map.
 *
 * @method static array call(Actor $actor)
 */
final class MapPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    public function handle(): array
    {
        $events = Event::select([
            Event::ID,
            Event::NAME,
            Event::EVENT_START,
            Event::EVENT_END,
            Event::VENUE_ADDRESS,
            Event::ORGANIZATION_ID,
            Event::LATITUDE,
            Event::LONGITUDE,
        ])
            ->upcoming()
            ->whereNotNull(Event::LATITUDE)
            ->whereNotNull(Event::LONGITUDE)
            ->orderBy(Event::EVENT_START)
            ->get();

        return [
            'events' => MapEventResource::collection($events),
            'hosting_organizations' => new HostingOrganizationOptions(
                GetOrganizationsForPicker::call(trooper: $this->actor, data: []),
            ),
            'has_map' => (bool) config('services.google.maps_api_key'),
        ];
    }
}
