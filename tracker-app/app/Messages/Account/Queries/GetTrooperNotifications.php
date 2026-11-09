<?php

declare(strict_types=1);

namespace App\Messages\Account\Queries;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Database\Eloquent\Collection;

/**
 * Retrieves all push notifications for the authenticated trooper.
 *
 * Returns notifications ordered by most recent first.
 *
 * @method static Collection call(Trooper $trooper)
 */
final class GetTrooperNotifications extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    /**
     * Execute the query to retrieve trooper notifications.
     *
     * @return Collection<int, TrooperNotification> Collection of notifications ordered by latest first
     */
    public function handle(): Collection
    {
        return $this->trooper->notifications()->latest()->get();
    }
}
