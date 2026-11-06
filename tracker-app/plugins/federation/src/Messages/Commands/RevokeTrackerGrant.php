<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Messages\Commands;

use Hyperdrive\Message;
use TroopTracker\Plugins\Federation\Models\TrackerGrant;

/**
 * Stops another tracker reading a trooper's history here. Its next refresh is
 * refused, and it removes the troops it copied.
 *
 * @method static void call(TrackerGrant $grant)
 */
final class RevokeTrackerGrant extends Message
{
    public function __construct(private readonly TrackerGrant $grant) {}

    public function handle(): void
    {
        $this->grant->delete();
    }
}
