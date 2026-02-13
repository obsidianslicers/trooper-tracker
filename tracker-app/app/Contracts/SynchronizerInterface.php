<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for synchronizing data from external systems.
 *
 * Implementations of this interface handle bidirectional data synchronization
 * between Troop Tracker and external platforms (e.g., XenForo forums).
 * This enables automatic updates of trooper profiles, costumes, and membership
 * information from authoritative external sources.
 */
interface SynchronizerInterface
{
    /**
     * Synchronize all costume data from the external system.
     *
     * Fetches costume records from the external platform and updates
     * the local database to match. This typically includes costume types,
     * approval status, and associated trooper assignments.
     */
    public function syncCostumes(): void;

    /**
     * Synchronize all member records from the external system.
     *
     * Performs a full synchronization of all trooper/member data from
     * the external platform. This is typically used for initial setup
     * or periodic full refreshes to ensure data consistency.
     */
    public function syncAllMembers(): void;

    /**
     * Synchronize a specific member's data from the external system.
     *
     * Updates a single trooper's profile data by fetching their current
     * information from the external platform using their unique identifier.
     * This is useful for on-demand updates or after specific events.
     *
     * @param  string  $identifier  The unique identifier for the member in the external system
     */
    public function syncMember(string $identifier): void;
}
