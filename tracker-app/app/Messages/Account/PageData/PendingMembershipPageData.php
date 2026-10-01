<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Messages\Account\Resources\PendingTrooper;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Retrieves data for the Pending Membership page.
 *
 * This query message responds with data necessary for rendering the Pending Membership page, including trooper details,
 * notifications, costumes, memberships, friends, and minors.
 * Used by frontend clients to display the renewal interface for visitor troopers.
 *
 * @method static array<string, mixed> call()
 */
final class PendingMembershipPageData extends Message
{
    /**
     * Constructs the PendingMembershipPageData message.
     *
     * @param  Actor&Trooper  $actor  The actor representing the current user
     */
    public function __construct(
        private readonly Actor $actor
    ) {
    }

    /**
     * Retrieves data for the Pending Membership page as a nested associative array.
     *
     * @return array Data array with trooper details, notifications, costumes, memberships, friends, and minors
     */
    public function handle(): array
    {
        $data = [
            'trooper' => $this->getPendingTrooper(),
        ];

        return $data;
    }

    private function getPendingTrooper(): PendingTrooper
    {
        return new PendingTrooper($this->actor);
    }
}
