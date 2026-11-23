<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Create Event form's starting values: blank details, the host and type it starts with,
 * and the logistics questions on "Yes", as they always have been.
 *
 * @mixin Event
 */
class NewEventFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $blank = array_fill_keys([
            Event::NAME, Event::EVENT_START, Event::EVENT_END, Event::EVENT_WEBSITE,
            Event::LATITUDE, Event::LONGITUDE, Event::VENUE, Event::VENUE_ADDRESS,
            Event::CONTACT_NAME, Event::CONTACT_PHONE, Event::CONTACT_EMAIL,
            Event::EXPECTED_ATTENDEES, Event::REQUESTED_NUMBER_CHARACTERS,
            Event::REQUESTED_CHARACTER_TYPES, Event::SHIFTS_ALLOWED, Event::TROOPERS_ALLOWED,
            Event::HANDLERS_ALLOWED, Event::FRIENDS_ALLOWED, Event::GUESTS_ALLOWED,
            Event::AMENITIES, Event::COMMENTS, Event::REFERRED_BY,
        ], null);

        return [
            ...$blank,
            Event::ORGANIZATION_ID => $this->organization_id,
            Event::EVENT_TYPE_ID => $this->event_type_id,
            Event::STATUS => $this->status->value,
            Event::TENTATIVE_SIGNUPS_ALLOWED => true,
            Event::SECURE_STAGING_AREA => true,
            Event::PARKING_AVAILABLE => true,
            Event::ALLOW_BLASTERS => true,
            Event::ALLOW_PROPS => true,
            Event::ACCESSIBLE => true,
            Event::REQUIRE_MISSION_BRIEF_ACK => false,
        ];
    }
}
