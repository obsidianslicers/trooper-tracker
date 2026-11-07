<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Commands;

use Hyperdrive\Message;
use TroopTracker\Plugins\Holonet\Models\HolonetGrant;

/**
 * Stops another tracker reading a trooper's history here. Its next refresh is
 * refused, and it removes the troops it copied.
 *
 * @method static void call(HolonetGrant $grant)
 */
final class RevokeTrackerGrant extends Message
{
    public function __construct(private readonly HolonetGrant $grant) {}

    public function handle(): void
    {
        $this->grant->delete();
    }
}
