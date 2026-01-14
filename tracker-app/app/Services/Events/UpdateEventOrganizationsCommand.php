<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Event;
use App\Models\EventOrganization;

/**
 * Service for updating an event's organization attendance settings.
 *
 * This service synchronizes organization attendance permissions and limits
 * for an event. It manages which organizations can attend and their specific
 * trooper and handler allowances in the event_organizations pivot table.
 */
class UpdateEventOrganizationsCommand
{
    /**
     * Update event organization attendance settings.
     *
     * Synchronizes the event's organization relationships with attendance
     * permissions and limits. Organizations not in the data array are set
     * to can_attend=false with null limits. Provided organizations are
     * updated with their specified settings.
     *
     * @param Event $event The event to update organization settings for
     * @param array $data Organization settings keyed by organization_id
     * @return void
     */
    public function __invoke(Event $event, array $data): void
    {
        $pivot_data = $event->organizations()
            ->get()
            ->mapWithKeys(fn($org) => [
                $org->id => [
                    EventOrganization::CAN_ATTEND => false,
                    EventOrganization::TROOPERS_ALLOWED => null,
                    EventOrganization::HANDLERS_ALLOWED => null,
                ]
            ])
            ->toArray();

        //  merge arrays - left wins    
        $updates = $data + $pivot_data;

        $event->organizations()->syncWithoutDetaching($updates);
    }
}