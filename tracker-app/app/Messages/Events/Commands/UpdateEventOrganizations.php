<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use App\Models\EventOrganization;
use Hyperdrive\Message;

/**
 * Command to update event organization attendance settings.
 *
 * Synchronizes organization attendance permissions and limits for an event.
 * Manages which organizations can attend and their specific trooper and handler
 * allowances in the event_organizations pivot table.
 *
 * @method static void call(Event $event, array $data)
 */
final class UpdateEventOrganizations extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event to update organization settings for
     * @param  array<int, array<string, mixed>>  $data  Organization settings keyed by organization_id
     */
    public function __construct(
        private readonly Event $event,
        private readonly array $data) {}

    /**
     * Execute the command to update event organization settings.
     *
     * @return null
     */
    public function handle(): void
    {
        $pivot_data = $this->event->organizations()
            ->get()
            ->mapWithKeys(fn ($org) => [
                $org->id => [
                    EventOrganization::CAN_ATTEND => false,
                    EventOrganization::TROOPERS_ALLOWED => null,
                    EventOrganization::HANDLERS_ALLOWED => null,
                ],
            ])
            ->toArray();

        //  merge arrays - left wins
        $updates = array_replace_recursive($pivot_data, $this->data);

        $this->event->organizations()->syncWithoutDetaching($updates);
    }
}
