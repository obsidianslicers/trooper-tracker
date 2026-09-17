<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Command to apply a bulk event-roster form submission: per-trooper status,
 * costume, and org-credit changes, plus per-guest status changes.
 *
 * @see UpdateEventRosterCommandHandler
 */
readonly class UpdateEventRosterCommand
{
    /**
     * @param  array<int, array<string, mixed>>  $validated_troopers  Submitted trooper input, keyed by event_trooper id.
     * @param  array<int, array<string, mixed>>  $validated_guests  Submitted guest input, keyed by event_guest id.
     * @param  array<int, int>|null  $allowed_org_ids  Moderator's scoped org ids, or null for no restriction (admin).
     */
    public function __construct(
        public Event $event,
        public Trooper $auth_trooper,
        public array $validated_troopers,
        public array $validated_guests,
        public ?array $allowed_org_ids,
    ) {}
}
