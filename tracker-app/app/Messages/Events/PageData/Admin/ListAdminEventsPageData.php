<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Enums\EventStatus;
use App\Messages\Events\Queries\GetEventsForModerator;
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
            'filters' => [
                'status' => $this->status,
                'search_term' => $this->search_term ?? '',
                'organization_id' => $this->organization_id,
            ],
            'organization' => $this->organization(),
            'statuses' => EventStatus::toValueLabels(),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function organization(): ?array
    {
        if ($this->organization_id === null)
        {
            return null;
        }

        $organization = Organization::findOrFail($this->organization_id);

        return ['id' => $organization->id, 'name' => $organization->name];
    }
}
