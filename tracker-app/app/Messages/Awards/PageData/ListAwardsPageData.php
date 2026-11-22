<?php

declare(strict_types=1);

namespace App\Messages\Awards\PageData;

use App\Messages\Awards\Queries\GetAwardsForModerator;
use App\Messages\Awards\Resources\AwardRowResource;
use App\Models\Filters\AwardFilter;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Command Staff awards list: one page of awards plus the search and organization filters.
 *
 * @method static array call(Actor $actor, AwardFilter $filter, string|null $search_term = null, int|null $organization_id = null)
 */
final class ListAwardsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly AwardFilter $filter,
        private readonly ?string $search_term = null,
        private readonly ?int $organization_id = null,
    ) {}

    public function handle(): array
    {
        $awards = GetAwardsForModerator::call(filter: $this->filter, moderator: $this->actor);

        return [
            'awards' => AwardRowResource::collection($awards),
            'search_term' => $this->search_term ?? '',
            'organization' => $this->getOrganization(),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function getOrganization(): ?array
    {
        if ($this->organization_id === null)
        {
            return null;
        }

        $organization = Organization::findOrFail($this->organization_id);

        return ['id' => $organization->id, 'name' => $organization->name];
    }
}
