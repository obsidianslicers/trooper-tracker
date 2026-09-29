<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Retrieves data for the Renew Visitor page.
 *
 * This query message responds with data necessary for rendering the Renew Visitor page, including trooper details,
 * notifications, costumes, memberships, friends, and minors.
 * Used by frontend clients to display the renewal interface for visitor troopers.
 *
 * @method static array<string, mixed> call()
 */
final class RenewVisitorPageData extends Message
{
    /**
     * Constructs the RenewVisitorPageData message.
     *
     * @param  Actor&Trooper  $actor  The actor representing the current user
     */
    public function __construct(
        private readonly Actor $actor
    ) {}

    /**
     * Retrieves data for the Renew Visitor page as a nested associative array.
     *
     * @return array Data array with trooper details, notifications, costumes, memberships, friends, and minors
     */
    public function handle(): array
    {
        $data = [
            'visitor_expires_at' => $this->actor->visitor_expires_at,
        ];

        return $data;
    }
}
