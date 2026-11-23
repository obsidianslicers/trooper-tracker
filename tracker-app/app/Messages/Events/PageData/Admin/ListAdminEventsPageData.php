<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Enums\EventStatus;
use App\Messages\Events\Queries\GetEventsForModerator;
use App\Messages\Events\Resources\Admin\AdminEventListFilters;
use App\Messages\Events\Resources\Admin\AdminEventListOrganization;
use App\Messages\Events\Resources\Admin\ChoiceOptions;
use App\Messages\Events\Resources\AdminEventRowResource;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Command Staff events list: one page of the events the actor manages, the filters in use,
 * and the statuses to filter by.
 *
 * @method static array call(Actor $actor, EventFilter $filter, ?string $status = null, ?string $search_term = null, ?int $organization_id = null)
 */
final class ListAdminEventsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly EventFilter $filter,
        private readonly ?string $status = null,
        private readonly ?string $search_term = null,
        private readonly ?int $organization_id = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'events' => AdminEventRowResource::collection(
                GetEventsForModerator::call(filter: $this->filter, moderator: $this->actor)
            ),
            'filters' => new AdminEventListFilters($this->status, $this->search_term, $this->organization_id),
            'organization' => $this->organization_id === null
                ? null
                : new AdminEventListOrganization(Organization::findOrFail($this->organization_id)),
            'statuses' => new ChoiceOptions(EventStatus::toArray()),
        ];
    }
}
