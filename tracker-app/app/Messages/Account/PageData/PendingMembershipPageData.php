<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use Hyperdrive\Message;

/**
 * The Pending Membership page (account/PendingMembership). It only says the application is under
 * review, and plugins add their sections through a slot, so it needs no data of its own.
 *
 * @method static array<string, mixed> call()
 */
final class PendingMembershipPageData extends Message
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [];
    }
}
