<?php

declare(strict_types=1);

namespace App\Messages\Notices\PageData;

use App\Messages\Notices\Queries\GetNoticesForModerator;
use App\Messages\Notices\Resources\NoticeRowResource;
use App\Messages\Organizations\Resources\OrganizationFilterResource;
use App\Models\Filters\NoticeFilter;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Command Staff notices list: one page of notices plus the scope and organization filters.
 *
 * @method static array call(Actor $actor, NoticeFilter $filter, string $scope = 'active', int|null $organization_id = null)
 */
final class ListNoticesPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly NoticeFilter $filter,
        private readonly string $scope = 'active',
        private readonly ?int $organization_id = null,
    ) {}

    public function handle(): array
    {
        $notices = GetNoticesForModerator::call(filter: $this->filter, moderator: $this->actor);

        return [
            'notices' => NoticeRowResource::collection($notices),
            'scope' => $this->scope,
            'organization' => $this->getOrganization(),
        ];
    }

    private function getOrganization(): ?OrganizationFilterResource
    {
        if ($this->organization_id === null)
        {
            return null;
        }

        return new OrganizationFilterResource(Organization::findOrFail($this->organization_id));
    }
}
