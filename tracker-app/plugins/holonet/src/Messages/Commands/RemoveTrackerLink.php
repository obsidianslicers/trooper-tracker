<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Commands;

use Hyperdrive\Message;
use TroopTracker\Plugins\Holonet\Models\HolonetLink;
use TroopTracker\Plugins\Holonet\Support\TrackerClient;

/**
 * Removes a link and the troops copied through it, and asks the other tracker
 * to forget the access key.
 *
 * @method static void call(HolonetLink $link)
 */
final class RemoveTrackerLink extends Message
{
    public function __construct(private readonly HolonetLink $link) {}

    public function handle(TrackerClient $client): void
    {
        if ($this->link->isActive())
        {
            $client->revoke($this->link->tracker_url, $this->link->token);
        }

        $this->link->troops()->delete();
        $this->link->delete();
    }
}
