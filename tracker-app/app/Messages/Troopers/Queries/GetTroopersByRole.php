<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve all troopers with administrator role.
 *
 * Returns all active troopers who have the ADMINISTRATOR membership_role
 * on any organization assignment. Used for sending system notifications
 * and administrative functions.
 *
 * @method static Collection<int, Trooper> call(MembershipRole $membership_role)
 */
final class GetTroopersByRole extends Message
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all administrators.
     */
    public function __construct(private readonly MembershipRole $membership_role) {}

    /**
     * Execute the query to retrieve administrator troopers.
     *
     * Process:
     * 1. Filter active troopers
     * 2. Filter by membership_role = ADMINISTRATOR
     * 3. Order by name
     * 4. Return collection of Trooper models
     *
     * @return Collection<int, Trooper> Collection of administrator troopers
     */
    public function handle(): Collection
    {
        return Trooper::active()
            ->where(Trooper::MEMBERSHIP_ROLE, $this->membership_role)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }
}
