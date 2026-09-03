<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * Shared validation rules for Event creation and updates.
 *
 * Provides common validation rules used by both CreateRequest and UpdateRequest.
 * Includes validation for:
 * - Event details (name, status, dates, website)
 * - Contact information (name, phone, email)
 * - Venue details (name, address, city, state, zip, country)
 * - Charity information (name, hours, direct/indirect funds, notes)
 * - Capacity limits (troopers, handlers, friends allowed, tentative signups)
 * - Event specifics (expected attendees, requested characters)
 * - Venue features (secure staging, blasters, props, parking, accessibility, amenities)
 * - Geographic coordinates (latitude/longitude)
 * - Organization associations (via organizations array with attendance and limits)
 * - Miscellaneous fields (comments, referred by, source)
 */
trait CommonRules
{
    /**
     * Get common validation rules for event requests
     *
     * Returns an array of validation rules applied to both event creation
     * and update operations. All rules ensure data integrity while allowing
     * flexibility for optional fields.
     *
     * @return array<string, mixed> The validation rules array
     */
    protected function getCommonRules(): array
    {
        return [
            Event::ORGANIZATION_ID => [
                'required',
                Rule::exists(Organization::class, Organization::ID)
                    ->whereIn('id', Organization::moderatedBy($this->user())->pluck('id')),
            ],
            Event::NAME => ['required', 'string', 'max:128'],
            Event::TYPE => ['required', 'string', 'max:32', EventType::toValidator()],
            Event::STATUS => ['required', 'string', 'max:16', EventStatus::toValidator()],
            Event::SHIFTS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
            Event::TROOPERS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
            Event::HANDLERS_ALLOWED => ['nullable', 'integer', 'between:0,99999'],
            Event::FRIENDS_ALLOWED => ['nullable', 'integer', 'between:0,99999'],
            Event::GUESTS_ALLOWED => ['nullable', 'integer', 'between:0,99999'],
            Event::TENTATIVE_SIGNUPS_ALLOWED => ['boolean'],
            Event::CONTACT_NAME => ['nullable', 'string', 'max:128'],
            Event::CONTACT_PHONE => ['nullable', 'string', 'max:128'],
            Event::CONTACT_EMAIL => ['nullable', 'email', 'max:128'],

            Event::VENUE => ['nullable', 'string', 'max:256'],
            Event::VENUE_ADDRESS => ['nullable', 'string', 'max:256'],

            Event::EVENT_START => ['required', 'date'],
            Event::EVENT_END => ['required', 'date', 'after:'.Event::EVENT_START],
            Event::EVENT_WEBSITE => ['nullable', 'string', 'max:512'],

            Event::EXPECTED_ATTENDEES => ['nullable', 'integer', 'min:0'],
            Event::REQUESTED_NUMBER_CHARACTERS => ['nullable', 'integer', 'min:0'],
            Event::REQUESTED_CHARACTER_TYPES => ['nullable', 'string'],

            Event::SECURE_STAGING_AREA => ['boolean'],
            Event::ALLOW_BLASTERS => ['boolean'],
            Event::ALLOW_PROPS => ['boolean'],
            Event::PARKING_AVAILABLE => ['boolean'],
            Event::ACCESSIBLE => ['boolean'],
            Event::REQUIRE_MISSION_BRIEF_ACK => ['boolean'],
            Event::CREATE_FORUM_THREAD => ['boolean'],
            Event::THREAD_ID => ['nullable', 'integer', 'min:1'],
            Event::POST_ID => ['nullable', 'integer', 'min:1'],

            Event::AMENITIES => ['nullable', 'string'],
            Event::COMMENTS => ['nullable', 'string'],
            Event::REFERRED_BY => ['nullable', 'string', 'max:1024'],
            Event::SOURCE => ['nullable', 'string'],
            Event::LATITUDE => ['nullable', 'numeric', 'between:-90,90'],
            Event::LONGITUDE => ['nullable', 'numeric', 'between:-180,180'],

            'organizations.*.'.EventOrganization::CAN_ATTEND => ['boolean'],
            'organizations.*.'.EventOrganization::TROOPERS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
            'organizations.*.'.EventOrganization::HANDLERS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
        ];
    }
}
