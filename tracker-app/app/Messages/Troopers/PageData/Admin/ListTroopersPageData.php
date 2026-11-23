<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Admin;

use App\Enums\MembershipRole;
use App\Messages\Organizations\Queries\GetModeratedOrganizationOptions;
use App\Messages\Troopers\Queries\GetTroopersForModerator;
use App\Messages\Troopers\Resources\MembershipRoleOptions;
use App\Messages\Troopers\Resources\TrooperListFiltersResource;
use App\Messages\Troopers\Resources\TrooperListOrganizationResource;
use App\Messages\Troopers\Resources\TrooperListSortColumns;
use App\Messages\Troopers\Resources\TrooperRowResource;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Command Staff troopers list: one page of troopers, the filters in use, and the choices
 * for each filter.
 *
 * @method static array call(Actor $actor, TrooperFilter $filter, ?string $sort_col = null, ?string $sort_dir = null, ?string $search_term = null, ?string $membership_role = null, ?int $organization_id = null)
 */
final class ListTroopersPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly TrooperFilter $filter,
        private readonly ?string $sort_col = null,
        private readonly ?string $sort_dir = null,
        private readonly ?string $search_term = null,
        private readonly ?string $membership_role = null,
        private readonly ?int $organization_id = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $sort_col = array_key_exists((string) $this->sort_col, $this->sortColumns())
            ? (string) $this->sort_col
            : Trooper::DISPLAY_NAME;
        $sort_dir = $this->sort_dir === 'desc' ? 'desc' : 'asc';

        $organization_options = GetModeratedOrganizationOptions::call(trooper: $this->actor);

        return [
            'troopers' => TrooperRowResource::collection(GetTroopersForModerator::call(
                moderator: $this->actor,
                filter: $this->filter,
                sort_col: $sort_col,
                sort_dir: $sort_dir,
            )),
            'filters' => new TrooperListFiltersResource([
                'search_term' => $this->search_term ?? '',
                'membership_role' => $this->membership_role,
                'organization_id' => $this->organization_id,
                'sort_col' => $sort_col,
                'sort_dir' => $sort_dir,
            ]),
            'organization' => $this->organization(),
            'sort_columns' => new TrooperListSortColumns($this->sortColumns()),
            'roles' => new MembershipRoleOptions(MembershipRole::cases()),
            'organization_options' => array_merge(
                [['value' => null, 'label' => __('admin.All Groups')]],
                $organization_options,
            ),
        ];
    }

    private function organization(): ?TrooperListOrganizationResource
    {
        if ($this->organization_id === null)
        {
            return null;
        }

        $organization = Organization::moderatedBy($this->actor)->find($this->organization_id);

        return $organization ? new TrooperListOrganizationResource($organization) : null;
    }

    /**
     * @return array<string, string> column => header label
     */
    private function sortColumns(): array
    {
        return [
            Trooper::DISPLAY_NAME => __('admin.Name / Email'),
            Trooper::MEMBERSHIP_ROLE => __('admin.Role'),
            Trooper::MEMBERSHIP_STATUS => __('admin.Status'),
            Trooper::LAST_ACTIVE_AT => __('admin.Last Seen'),
        ];
    }
}
