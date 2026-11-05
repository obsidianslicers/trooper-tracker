<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Exceptions\ForumGroupUpdateException;
use App\Models\Trooper;
use App\Plugins\PluginForumGroups;
use Hyperdrive\Message;

/**
 * Adds and removes forum user groups on a trooper's linked forum account,
 * through the forum group provider a plugin set.
 *
 * Groups not named in either list are left untouched.
 *
 * @method static void call(Trooper $trooper, array $group_ids_to_add = [], array $group_ids_to_remove = [])
 */
final class UpdateTrooperForumGroups extends Message
{
    /**
     * @param  array<int, int>  $group_ids_to_add
     * @param  array<int, int>  $group_ids_to_remove
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $group_ids_to_add = [],
        private readonly array $group_ids_to_remove = [],
    ) {}

    /**
     * @throws ForumGroupUpdateException
     */
    public function handle(PluginForumGroups $forum_groups): void
    {
        if ($this->group_ids_to_add === [] && $this->group_ids_to_remove === [])
        {
            return;
        }

        $provider = $forum_groups->provider();

        if ($provider === null)
        {
            throw new ForumGroupUpdateException('Forum groups are not available.');
        }

        $provider->updateGroups($this->trooper, $this->group_ids_to_add, $this->group_ids_to_remove);
    }
}
