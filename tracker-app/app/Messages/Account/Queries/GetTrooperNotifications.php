<?php

declare(strict_types=1);

namespace App\Messages\Account\Queries;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Database\Eloquent\Collection;

/**
 * Retrieves push notifications for the authenticated trooper: all of them for the
 * notifications page, or the most recent few for the bell in the navbar.
 *
 * Returns notifications ordered by most recent first.
 *
 * @method static Collection call(Trooper $trooper, ?int $limit = null)
 */
final class GetTrooperNotifications extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly ?int $limit = null,
    ) {}

    /**
     * Execute the query to retrieve trooper notifications.
     *
     * @return Collection<int, TrooperNotification> Collection of notifications ordered by latest first
     */
    public function handle(): Collection
    {
        return $this->trooper->notifications()
            ->latest()
            ->when($this->limit !== null, fn ($query) => $query->limit($this->limit))
            ->get();
    }
}
