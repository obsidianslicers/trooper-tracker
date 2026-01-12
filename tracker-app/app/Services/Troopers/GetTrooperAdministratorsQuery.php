<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Retrieves all troopers with administrator privileges.
 *
 * This query service fetches troopers who have been granted administrator role,
 * which gives them full access to manage the application including:
 * - Approving pending trooper registrations
 * - Managing organizations, regions, and units
 * - Creating and managing events
 * - Moderating content and users
 * - Viewing system logs and exception reports
 *
 * Used primarily for:
 * - Sending critical system notifications (e.g., exception alerts)
 * - Displaying admin lists in management interfaces
 * - Audit logging of administrative actions
 *
 * This is a **query service** (not a command) - it retrieves data without side effects.
 */
class GetTrooperAdministratorsQuery
{
    /**
     * Execute the query to retrieve all administrator troopers.
     *
     * Filters troopers by membership_role = ADMINISTRATOR enum value.
     * Returns all matching records without pagination.
     *
     * @return Collection<int, Trooper> Collection of troopers with administrator role.
     */
    public function __invoke(): Collection
    {
        return Trooper::where(Trooper::MEMBERSHIP_ROLE, MembershipRole::ADMINISTRATOR)->get();
    }
}
