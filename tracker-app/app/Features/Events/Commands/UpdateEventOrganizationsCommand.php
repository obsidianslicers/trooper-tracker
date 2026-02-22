<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;

/**
 * Command to update event organization attendance settings.
 *
 * Synchronizes organization attendance permissions and limits for an event.
 * Manages which organizations can attend and their specific trooper and handler
 * allowances in the event_organizations pivot table.
 *
 * @see UpdateEventOrganizationsCommandHandler
 */
readonly class UpdateEventOrganizationsCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event to update organization settings for
     * @param  array<int, array<string, mixed>>  $data  Organization settings keyed by organization_id
     */
    public function __construct(
        public Event $event,
        public array $data) {}
}
