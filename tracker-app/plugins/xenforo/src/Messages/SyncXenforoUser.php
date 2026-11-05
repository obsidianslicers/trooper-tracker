<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Messages;

use App\Models\Trooper;
use Hyperdrive\Message;
use TroopTracker\Plugins\Xenforo\Services\XenforoUserSyncService;

/**
 * Pushes a trooper's details and forum groups to their linked XenForo user.
 *
 * @method static void call(Trooper $trooper)
 */
final class SyncXenforoUser extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    public function handle(XenforoUserSyncService $sync): void
    {
        $sync->syncTrooper($this->trooper);
    }
}
