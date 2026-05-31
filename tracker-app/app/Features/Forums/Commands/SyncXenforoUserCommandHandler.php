<?php

declare(strict_types=1);

namespace App\Features\Forums\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Services\Forums\XenforoUserSyncService;

/**
 * @implements CommandHandlerInterface<SyncXenforoUserCommand>
 */
readonly class SyncXenforoUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(private XenforoUserSyncService $sync_service) {}

    public function __invoke(object $message): mixed
    {
        $this->sync_service->syncTrooper($message->trooper);

        return null;
    }
}
