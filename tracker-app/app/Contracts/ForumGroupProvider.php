<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\ForumGroupUpdateException;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * User groups on a community forum that moderators can ask to give a trooper
 * or take away, through a permission request. A plugin registers one with
 * setForumGroupProvider().
 */
interface ForumGroupProvider
{
    /**
     * Whether the forum is set up well enough to be used right now.
     */
    public function isAvailable(): bool;

    /**
     * Groups a moderator may request.
     *
     * @return Collection<int, array{id:int,title:string,banner_image_url:?string}>
     */
    public function requestableGroups(): Collection;

    public function hasAccount(Trooper $trooper): bool;

    /**
     * The trooper's current group IDs, or null when they cannot be read.
     *
     * @return array<int, int>|null
     */
    public function currentGroupIds(Trooper $trooper): ?array;

    /**
     * Add and remove groups. Groups in neither list are left alone.
     *
     * @param  array<int, int>  $group_ids_to_add
     * @param  array<int, int>  $group_ids_to_remove
     *
     * @throws ForumGroupUpdateException
     */
    public function updateGroups(Trooper $trooper, array $group_ids_to_add, array $group_ids_to_remove): void;
}
