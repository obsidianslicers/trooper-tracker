<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Models\PermissionRequest;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Lists the most recent permission requests a trooper has submitted.
 *
 * @method static Collection<int, PermissionRequest> call(Trooper $requester, int $limit = 25)
 */
final class GetRequestedPermissionRequests extends Message
{
    public function __construct(
        private readonly Trooper $requester,
        private readonly int $limit = 25,
    ) {}

    /**
     * @return Collection<int, PermissionRequest>
     */
    public function handle(): Collection
    {
        return PermissionRequest::with(['trooper', 'requester', 'reviewer'])
            ->where(PermissionRequest::REQUESTER_TROOPER_ID, $this->requester->id)
            ->latest(PermissionRequest::CREATED_AT)
            ->latest(PermissionRequest::ID)
            ->limit($this->limit)
            ->get();
    }
}
