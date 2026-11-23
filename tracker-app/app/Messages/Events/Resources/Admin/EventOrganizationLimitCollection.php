<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use App\Models\EventOrganization;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/**
 * Every club troopers can sign up from, with whether it attends and its limits, for the
 * Organizations section of Create Event and the event's Overview tab.
 *
 * Without the event's own organizations (a new event), each club starts at its own default.
 */
class EventOrganizationLimitCollection extends ResourceCollection
{
    /**
     * @param  Collection<int, Organization>  $organizations  The clubs, in the order shown
     * @param  Collection<int, Organization>|null  $event_organizations  The event's clubs, with the pivot
     */
    public function __construct(
        Collection $organizations,
        private readonly ?Collection $event_organizations = null,
    ) {
        parent::__construct($organizations);
    }

    /**
     * @return array<int, array{id: int, name: string, image_url: string, can_attend: bool,
     *     troopers_allowed: int|null, handlers_allowed: int|null}>
     */
    public function toArray(Request $request): array
    {
        $limits = $this->event_organizations?->keyBy(Organization::ID);

        return $this->collection
            ->map(function (Organization $organization) use ($limits): array
            {
                $pivot = $limits?->get($organization->id)?->pivot;
                $can_attend = $limits === null
                    ? (bool) $organization->can_attend_default
                    : (bool) ($pivot?->{EventOrganization::CAN_ATTEND} ?? false);

                return [
                    Organization::ID => $organization->id,
                    Organization::NAME => $organization->name,
                    'image_url' => map_image_url(
                        $organization->image_path_sm,
                        'img/icons/organization-32x32.png'
                    ),
                    'can_attend' => $can_attend,
                    'troopers_allowed' => $can_attend ? $pivot?->troopers_allowed : null,
                    'handlers_allowed' => $can_attend ? $pivot?->handlers_allowed : null,
                ];
            })
            ->values()
            ->all();
    }
}
