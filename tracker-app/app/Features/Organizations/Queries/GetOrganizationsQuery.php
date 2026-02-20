<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

/**
 * Query to retrieve all organizations of type 'organization'.
 *
 * Returns all top-level organizations (not regions or units) ordered by name.
 * This query has no parameters and returns all organization-type records.
 *
 * @see GetOrganizationsQueryHandler
 */
readonly class GetOrganizationsQuery {}
