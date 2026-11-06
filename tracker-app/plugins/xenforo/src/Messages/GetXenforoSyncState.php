<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Messages;

use App\Models\Trooper;
use Hyperdrive\Message;
use TroopTracker\Plugins\Xenforo\Services\XenforoUserSyncService;

/**
 * What a sync would change for a trooper, without writing to XenForo.
 *
 * @method static array call(Trooper $trooper)
 */
final class GetXenforoSyncState extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(XenforoUserSyncService $sync): array
    {
        return $sync->debugSync($this->trooper);
    }
}
