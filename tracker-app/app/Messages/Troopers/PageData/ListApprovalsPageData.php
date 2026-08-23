<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Messages\Troopers\Resources\PendingTrooperApprovalsCollection;
use App\Models\Trooper;
use App\Messages\Troopers\Queries\Membership\GetPendingTrooperApprovals;
use App\Messages\Troopers\Queries\Membership\GetPendingTrooperRequests;
use App\Messages\Troopers\Resources\PendingTrooperRequestsCollection;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Retrieves data for the trooper approvals page.

 * This query message responds with the necessary data for displaying troopers pending approval,
 * including their details, notifications, costumes, memberships, friends, and minors.
 *
 * @method static array<string, mixed> call()
 */
final class ListApprovalsPageData extends Message
{
    /**
     * Constructs the ListApprovalsPageData message.
     * 
     * @param  Actor&Trooper  $actor  The actor (moderator/administrator) requesting the data
     */
    public function __construct(private readonly Actor $actor)
    {
    }

    /**
     * Retrieves trooper approvals as a nested associative array.
     *
     * @return array Configuration array with trooper approvals
     */
    public function handle(): array
    {
        $data = [
            'trooper_approvals' => $this->getTrooperApprovals(),
            'trooper_requests' => $this->getTrooperRequests(),
        ];

        return $data;
    }

    private function getTrooperApprovals(): PendingTrooperApprovalsCollection
    {
        $troopers = GetPendingTrooperApprovals::call(moderator: $this->actor);

        return new PendingTrooperApprovalsCollection($troopers);
    }

    private function getTrooperRequests(): PendingTrooperRequestsCollection
    {
        $troopers = GetPendingTrooperRequests::call(moderator: $this->actor);

        return new PendingTrooperRequestsCollection($troopers);
    }
}
