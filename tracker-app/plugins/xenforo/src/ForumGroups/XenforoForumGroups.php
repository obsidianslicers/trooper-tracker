<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\ForumGroups;

use App\Contracts\ForumGroupProvider;
use App\Exceptions\ForumGroupUpdateException;
use App\Models\Trooper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

/**
 * XenForo secondary user groups, for permission requests.
 *
 * Groups mapped to organizations (active/reserve/retired) are left out because
 * the user sync owns them and would undo any manual change. Groups granted by
 * user upgrades are left out for the same reason: XenForo adds and removes them
 * as upgrades start and expire.
 */
final class XenforoForumGroups implements ForumGroupProvider
{
    public const CACHE_KEY = 'xenforo_requestable_user_groups_v3';

    private const CACHE_SECONDS = 600;

    public function __construct(private readonly XenforoService $xenforo) {}

    public function isAvailable(): bool
    {
        return XenforoService::isConfigured();
    }

    public function requestableGroups(): Collection
    {
        $managed_ids = OrganizationForum::managedGroupIds();

        return collect($this->fetchGroups())
            ->reject(fn (array $group): bool => $managed_ids->contains($group['id']))
            ->values();
    }

    public function hasAccount(Trooper $trooper): bool
    {
        return $this->xenforo->resolve_user_id_for_trooper($trooper->id) !== null;
    }

    public function currentGroupIds(Trooper $trooper): ?array
    {
        $user_id = $this->xenforo->resolve_user_id_for_trooper($trooper->id);

        return $user_id === null ? null : $this->xenforo->getUserSecondaryGroupIds($user_id);
    }

    public function updateGroups(Trooper $trooper, array $group_ids_to_add, array $group_ids_to_remove): void
    {
        if ($group_ids_to_add === [] && $group_ids_to_remove === [])
        {
            return;
        }

        $user_id = $this->xenforo->resolve_user_id_for_trooper($trooper->id);

        if ($user_id === null)
        {
            throw new ForumGroupUpdateException("{$trooper->display_name} has no linked forum account.");
        }

        $current = $this->xenforo->getUserSecondaryGroupIds($user_id);

        if ($current === null)
        {
            throw new ForumGroupUpdateException('Could not read the forum account\'s current user groups.');
        }

        $desired = collect($current)
            ->diff($group_ids_to_remove)
            ->merge($group_ids_to_add)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        if ($desired->sort()->values()->all() !== collect($current)->sort()->values()->all())
        {
            $this->send($user_id, $desired->all());
        }
    }

    /**
     * @param  array<int, int>  $group_ids
     */
    private function send(int $user_id, array $group_ids): void
    {
        //  an empty array would be dropped from the form body, so send an empty
        //  value to clear every secondary group
        $payload = ['secondary_group_ids' => $group_ids === [] ? '' : $group_ids];

        $result = $this->xenforo->update_user($user_id, $payload);

        if ($result['status'] < 200 || $result['status'] >= 300)
        {
            throw new ForumGroupUpdateException(
                "The forum rejected the user group change (HTTP {$result['status']})."
            );
        }
    }

    /**
     * Empty or failed lookups are not cached so a forum outage or add-on fix
     * shows up on the next page load instead of after the cache expires.
     *
     * @return array<int, array{id:int,title:string,banner_image_url:?string}>
     */
    private function fetchGroups(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached))
        {
            return $cached;
        }

        $upgrade_ids = $this->xenforo->getUpgradeGroupIds() ?? [];

        $groups = collect($this->xenforo->getAllUserGroups() ?? [])
            ->reject(fn (array $group): bool => in_array($group['id'], $upgrade_ids, true))
            ->values()
            ->all();

        if ($groups !== [])
        {
            Cache::put(self::CACHE_KEY, $groups, self::CACHE_SECONDS);
        }

        return $groups;
    }
}
