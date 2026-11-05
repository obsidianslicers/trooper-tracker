<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Models\Trooper;
use App\Plugins\PluginForumGroups;
use Hyperdrive\Message;

/**
 * Reads a trooper's current forum group IDs.
 *
 * Returns null when no forum group provider is available, the trooper has no
 * linked forum account, or the forum could not be read.
 *
 * @method static array<int, int>|null call(Trooper $trooper)
 */
final class GetTrooperForumGroupIds extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return array<int, int>|null
     */
    public function handle(PluginForumGroups $forum_groups): ?array
    {
        return $forum_groups->provider()?->currentGroupIds($this->trooper);
    }
}
