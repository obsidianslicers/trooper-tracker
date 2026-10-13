<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Compares the organizations a requester wants a trooper to moderate with what
 * the trooper moderates today, and returns the organizations to add and remove.
 *
 * Picked children of a picked organization are dropped because moderating an
 * organization covers everything beneath it. Only organizations the requester
 * may manage are removed, so moderation outside their scope is left alone.
 *
 * @method static array{add:array<int,int>,remove:array<int,int>} call(
 *     Trooper $requester,
 *     Trooper $trooper,
 *     array $organization_ids
 * )
 */
final class GetModerationChanges extends Message
{
    /**
     * @param  array<int, int|string>  $organization_ids  The desired moderated organizations
     */
    public function __construct(
        private readonly Trooper $requester,
        private readonly Trooper $trooper,
        private readonly array $organization_ids,
    ) {}

    /**
     * @return array{add:array<int,int>,remove:array<int,int>}
     */
    public function handle(): array
    {
        $desired = $this->getDesiredIds();
        $current = $this->getCurrentIdsInScope();

        return [
            'add' => array_values(array_diff($desired, $current)),
            'remove' => array_values(array_diff($current, $desired)),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function getDesiredIds(): array
    {
        $ids = collect($this->organization_ids)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        $organizations = Organization::query()
            ->whereIn(Organization::ID, $ids)
            ->get([Organization::ID, Organization::NODE_PATH]);

        $covered_ids = $organizations
            ->filter(fn (Organization $child): bool => $organizations->contains(
                fn (Organization $parent): bool => $child->isWithin($parent)
            ))
            ->map(fn (Organization $organization): int => (int) $organization->id);

        return $ids->diff($covered_ids)->values()->all();
    }

    /**
     * @return array<int, int>
     */
    private function getCurrentIdsInScope(): array
    {
        $requestable_ids = GetRequestableOrganizations::call(requester: $this->requester)
            ->modelKeys();

        return collect($this->trooper->resolveModeratorOrgIds() ?? [])
            ->map(fn (int|string $id): int => (int) $id)
            ->intersect($requestable_ids)
            ->values()
            ->all();
    }
}
