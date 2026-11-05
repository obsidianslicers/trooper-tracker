<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Donations;

use App\Facades\TroopTrackerFacade;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Support\Collection;

/**
 * One trooper's forum account details, fetched at most once per request so the
 * service record's banners, profile link, donation table and totals share them.
 */
final class XenforoProfile
{
    /**
     * @var array<int, ?int>
     */
    private array $user_ids = [];

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $upgrade_history = [];

    public function __construct(private readonly XenforoService $xenforo) {}

    public function userId(Trooper $trooper): ?int
    {
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return null;
        }

        if (!array_key_exists($trooper->id, $this->user_ids))
        {
            $this->user_ids[$trooper->id] = $this->xenforo->resolve_user_id_for_trooper($trooper->id);
        }

        return $this->user_ids[$trooper->id];
    }

    public function profileUrl(Trooper $trooper): ?string
    {
        $user_id = $this->userId($trooper);

        return $user_id === null
            ? null
            : config('services.xenforo.base_url').'/members/'.$user_id.'/';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function upgradeHistory(Trooper $trooper): array
    {
        $user_id = $this->userId($trooper);

        if ($user_id === null)
        {
            return [];
        }

        return $this->upgrade_history[$user_id]
            ??= $this->xenforo->get_user_upgrade_history($user_id) ?? [];
    }

    /**
     * Group banners, primary group first, then by display order and title.
     *
     * @return Collection<int, array{title:string,banner_text:string,is_primary:bool,order:int}>
     */
    public function groupBanners(Trooper $trooper): Collection
    {
        $user_id = $this->userId($trooper);

        if ($user_id === null)
        {
            return collect();
        }

        $group_data = $this->xenforo->get_user_groups($user_id);

        return collect($group_data['userGroups'] ?? [])
            ->filter(fn (mixed $group): bool => is_array($group) && !empty($group['bannerText']))
            ->map(fn (array $group): array => [
                'title' => (string) ($group['title'] ?? ''),
                'banner_text' => (string) ($group['bannerText'] ?? ''),
                'is_primary' => (bool) ($group['isPrimary'] ?? false),
                'order' => (int) ($group['order'] ?? PHP_INT_MAX),
            ])
            ->sortBy(fn (array $group): string => sprintf(
                '%d-%010d-%s',
                $group['is_primary'] ? 0 : 1,
                $group['order'],
                strtolower($group['title'])
            ))
            ->values();
    }
}
