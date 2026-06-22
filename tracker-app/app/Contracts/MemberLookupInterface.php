<?php

declare(strict_types=1);

namespace App\Contracts;

interface MemberLookupInterface
{
    /**
     * Look up a member by their organization-specific identifier.
     *
     * Returns a normalized array with keys:
     *   identifier, formatted_identifier, full_name, status, standing,
     *   is_approved, unit_name, profile_url, thumbnail_url
     *
     * Returns null when the identifier is not found or the API is unreachable.
     */
    public function lookup(string $identifier): ?array;
}
