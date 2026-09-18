<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;
use App\Models\Trooper;

/**
 * Assigns troop credit to a single ATTENDED shift.
 *
 * Never changes the shift's costume — only which organization(s) receive credit for it.
 *
 * @see AssignEventTrooperCreditCommandHandler
 */
final readonly class AssignEventTrooperCreditCommand
{
    /**
     * @param  array<int, int>  $organization_ids
     * @param  bool  $is_override  True when the submitted org(s) come from the fallback picker
     *                             (no club was automatically eligible) rather than the normal
     *                             trooper-eligibility-derived list — see the handler for why this
     *                             needs a different code path.
     */
    public function __construct(
        public EventTrooper $event_trooper,
        public array $organization_ids,
        public Trooper $actor,
        public bool $is_override = false,
    ) {}
}
