<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * SynchronizerInterface
 *
 * Defines the contract for synchronizing trooper and costume data
 * from external sources (e.g., Google Sheets, XenForo forums).
 *
 * Implementations orchestrate data synchronization across one or more
 * organizations, updating trooper registrations, costume approvals,
 * and membership status from authoritative external data sources.
 *
 * @see App\Services\Synchronizers\BaseOrganizationService - Base implementation for synchronizers
 */
interface SynchronizerInterface
{
    /**
     * Execute the synchronization process.
     *
     * Coordinates one or more synchronization operations to fetch and update
     * trooper and costume data from external sources into the database.
     * The scope and specific operations depend on the concrete implementation
     * (e.g., syncing costumes, updating membership status, etc.).
     */
    public function synchronize(): void;
}
