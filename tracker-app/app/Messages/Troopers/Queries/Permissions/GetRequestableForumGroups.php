<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Facades\TroopTrackerFacade;
use App\Models\Organization;
use App\Services\Forums\XenforoService;
use Hyperdrive\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Lists the XenForo user groups a moderator may request for a trooper.
 *
 * Groups mapped to organizations (active/reserve/retired) are left out because
 * the XenForo user sync owns them and would undo any manual change.
 *
 * @method static Collection<int, array{id:int,title:string,banner_image_url:?string}> call()
 */
final class GetRequestableForumGroups extends Message
{
    private const CACHE_KEY = 'xenforo_requestable_user_groups_v2';

    private const CACHE_SECONDS = 600;

    public function __construct() {}

    /**
     * @return Collection<int, array{id:int,title:string,banner_image_url:?string}>
     */
    public function handle(XenforoService $xenforo): Collection
    {
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return collect();
        }

        $groups = $this->fetchGroups($xenforo);

        $managed_ids = $this->getManagedGroupIds();

        return collect($groups)
            ->reject(fn (array $group): bool => $managed_ids->contains($group['id']))
            ->values();
    }

    /**
     * Empty or failed lookups are not cached so a forum outage or add-on fix
     * shows up on the next page load instead of after the cache expires.
     *
     * @return array<int, array{id:int,title:string,banner_image_url:?string}>
     */
    private function fetchGroups(XenforoService $xenforo): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached))
        {
            return $cached;
        }

        $groups = $xenforo->getAllUserGroups() ?? [];

        if ($groups !== [])
        {
            Cache::put(self::CACHE_KEY, $groups, self::CACHE_SECONDS);
        }

        return $groups;
    }

    /**
     * @return Collection<int, int>
     */
    private function getManagedGroupIds(): Collection
    {
        $columns = [
            Organization::XENFORO_GROUP_ACTIVE_ID,
            Organization::XENFORO_GROUP_RESERVE_ID,
            Organization::XENFORO_GROUP_RETIRED_ID,
        ];

        return Organization::query()
            ->select($columns)
            ->get()
            ->flatMap(fn (Organization $org): array => array_values($org->only($columns)))
            ->filter(fn (mixed $id): bool => $id !== null)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();
    }
}
