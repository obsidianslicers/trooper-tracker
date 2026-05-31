<?php

declare(strict_types=1);

namespace App\Features\Forums\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Services\Forums\XenforoUserSyncService;

/**
 * @implements QueryHandlerInterface<GetXenforoSyncStateQuery>
 */
readonly class GetXenforoSyncStateQueryHandler implements QueryHandlerInterface
{
    public function __construct(private XenforoUserSyncService $sync_service) {}

    public function __invoke(object $message): mixed
    {
        return $this->sync_service->debugSync($message->trooper);
    }
}
