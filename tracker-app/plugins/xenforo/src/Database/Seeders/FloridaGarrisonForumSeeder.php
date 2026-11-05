<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;

/**
 * The Florida Garrison forum's event forums and user groups for each organization.
 *
 * Run after the organization seeders, with the XenForo plugin enabled:
 *   php artisan db:seed \
 *     --class="TroopTracker\Plugins\Xenforo\Database\Seeders\FloridaGarrisonForumSeeder"
 */
class FloridaGarrisonForumSeeder extends Seeder
{
    /**
     * Keyed by [parent name, organization name]; a null parent means a club.
     */
    private const FORUMS = [
        [null, '501st Legion', ['active_group_id' => 1415, 'retired_group_id' => 1429]],
        [null, 'Rebel Legion', ['active_group_id' => 1486]],
        [null, 'Mandalorian Mercs', ['active_group_id' => 1488]],
        [null, 'Dark Empire', ['active_group_id' => 1511]],
        [null, 'Droid Builders', ['active_group_id' => 1487]],
        [null, 'Saber Guild', ['active_group_id' => 1491]],
        ['501st Legion', 'Florida Garrison', ['active_group_id' => 18]],
        ['Florida Garrison', 'Everglades Squad', ['event_forum_id' => 9, 'archive_forum_id' => 107, 'active_group_id' => 44]],
        ['Florida Garrison', 'Makaze Squad', ['event_forum_id' => 8, 'archive_forum_id' => 109, 'active_group_id' => 45]],
        ['Florida Garrison', 'Tampa Bay Squad', ['event_forum_id' => 73, 'archive_forum_id' => 115, 'active_group_id' => 43]],
        ['Florida Garrison', 'Squad 7', ['event_forum_id' => 7, 'archive_forum_id' => 113, 'active_group_id' => 683]],
        ['Florida Garrison', 'Parjai Squad', ['event_forum_id' => 186, 'archive_forum_id' => 111, 'active_group_id' => 250]],
    ];

    public function run(): void
    {
        foreach (self::FORUMS as [$parent_name, $name, $settings])
        {
            $organization = $this->find($parent_name, $name);

            if ($organization !== null)
            {
                OrganizationForum::store($organization->id, $settings);
            }
        }
    }

    private function find(?string $parent_name, string $name): ?Organization
    {
        $query = Organization::query()->where(Organization::NAME, $name);

        if ($parent_name === null)
        {
            return $query->whereNull(Organization::PARENT_ID)->first();
        }

        $parent = Organization::query()->where(Organization::NAME, $parent_name)->first();

        return $parent === null ? null : $query->where(Organization::PARENT_ID, $parent->id)->first();
    }
}
