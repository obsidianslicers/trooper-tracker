<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The organization and its descendants a trooper can be assigned to. Visitors are limited to
 * the organization's own depth.
 *
 * @method static Collection<int, Organization> call(Organization $organization, bool $is_visitor)
 */
final class GetAssignableOrganizations extends Message
{
    public function __construct(
        private readonly Organization $organization,
        private readonly bool $is_visitor,
    ) {}

    public function handle(): Collection
    {
        $max_depth = $this->is_visitor ? (int) $this->organization->depth : null;

        return Organization::query()
            ->where(Organization::NODE_PATH, 'like', $this->organization->node_path.'%')
            ->when($max_depth !== null, fn ($query) => $query->where(Organization::DEPTH, '<=', $max_depth))
            ->orderBy(Organization::NODE_PATH)
            ->get();
    }
}
