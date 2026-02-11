<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;

/**
 * Query to retrieve troopers for use in picker/dropdown components.
 *
 * Supports filtering troopers by:
 * - Organization membership (specific organization)
 * - Search criteria (name, email, TK number via TrooperFilter)
 * - Role filters (admin, moderator, etc. via TrooperFilter)
 *
 * Results are ordered by trooper name for consistent UI display.
 *
 * @see GetTroopersForPickerQueryHandler
 */
readonly class GetTroopersForPickerQuery
{
    /**
     * Whether to filter to only organizations moderated by the trooper.
     *
     * @var bool
     */
    public readonly bool $moderated_only;

    /**
     * Optional organization ID to filter by (includes descendants via node_path).
     *
     * @var int|null
     */
    public readonly ?int $organization_id;

    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper requesting troopers
     * @param TrooperFilter $filter Search and role filtering criteria
     * @param array $data Query parameters:
     *                    - 'moderated_only' (bool): Filter to moderated organizations only (default: false)
     *                    - 'organization_id' (int|null): Filter to specific org and descendants (default: null)
     */
    public function __construct(
        public readonly Trooper $trooper,
        public readonly TrooperFilter $filter,
        array $data)
    {
        $this->moderated_only = filter_var($data['moderated_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->organization_id = empty($data['organization_id']) ? null : intval($data['organization_id']);
    }
}