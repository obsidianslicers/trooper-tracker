<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Plugins\PluginForumGroups;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Lists the forum user groups a moderator may request for a trooper, from the
 * forum group provider a plugin set. Empty when there is none.
 *
 * @method static Collection<int, array{id:int,title:string,banner_image_url:?string}> call()
 */
final class GetRequestableForumGroups extends Message
{
    public function __construct() {}

    /**
     * @return Collection<int, array{id:int,title:string,banner_image_url:?string}>
     */
    public function handle(PluginForumGroups $forum_groups): Collection
    {
        return $forum_groups->provider()?->requestableGroups() ?? collect();
    }
}
