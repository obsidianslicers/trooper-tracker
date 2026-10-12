<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Facades\TroopTrackerFacade;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Hyperdrive\Message;

/**
 * Reads a trooper's current XenForo secondary group IDs.
 *
 * Returns null when XenForo is not configured, the trooper has no linked
 * forum account, or the forum could not be read.
 *
 * @method static array<int, int>|null call(Trooper $trooper)
 */
final class GetTrooperForumGroupIds extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return array<int, int>|null
     */
    public function handle(XenforoService $xenforo): ?array
    {
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return null;
        }

        $xenforo_user_id = $xenforo->resolve_user_id_for_trooper($this->trooper->id);

        if ($xenforo_user_id === null)
        {
            return null;
        }

        return $xenforo->get_user_secondary_group_ids($xenforo_user_id);
    }
}
