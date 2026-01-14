<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventOrganization;

trait CommonRules
{
    protected function getCommonRules(): array
    {
        return [
            Event::NAME => ['required', 'string', 'max:128',],
            Event::STATUS => ['required', 'string', 'max:16', 'in:' . EventStatus::toValidator()],
            Event::TROOPERS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
            Event::HANDLERS_ALLOWED => ['nullable', 'integer', 'between:0,99999'],
            Event::FRIENDS_ALLOWED => ['nullable', 'integer', 'between:0,99999'],
            Event::TENTATIVE_SIGNUPS_ALLOWED => ['boolean'],
            Event::CONTACT_NAME => ['nullable', 'string', 'max:128'],
            Event::CONTACT_PHONE => ['nullable', 'string', 'max:128'],
            Event::CONTACT_EMAIL => ['nullable', 'email', 'max:128'],

            Event::VENUE => ['nullable', 'string', 'max:256'],
            Event::VENUE_ADDRESS => ['nullable', 'string', 'max:256'],
            Event::VENUE_CITY => ['nullable', 'string', 'max:128'],
            Event::VENUE_STATE => ['nullable', 'string', 'max:128'],
            Event::VENUE_ZIP => ['nullable', 'string', 'max:128'],
            Event::VENUE_COUNTRY => ['nullable', 'string', 'max:128'],

            Event::EVENT_START => ['required', 'date'],
            Event::EVENT_END => ['required', 'date', 'after:' . Event::EVENT_START],
            Event::EVENT_WEBSITE => ['nullable', 'string', 'max:512'],

            Event::EXPECTED_ATTENDEES => ['nullable', 'integer', 'min:0'],
            Event::REQUESTED_NUMBER_CHARACTERS => ['nullable', 'integer', 'min:0'],
            Event::REQUESTED_CHARACTER_TYPES => ['nullable', 'string'],

            Event::SECURE_STAGING_AREA => ['boolean'],
            Event::ALLOW_BLASTERS => ['boolean'],
            Event::ALLOW_PROPS => ['boolean'],
            Event::PARKING_AVAILABLE => ['boolean'],
            Event::ACCESSIBLE => ['boolean'],

            Event::AMENITIES => ['nullable', 'string'],
            Event::COMMENTS => ['nullable', 'string'],
            Event::REFERRED_BY => ['nullable', 'string', 'max:1024'],
            Event::SOURCE => ['nullable', 'string'],
            Event::LATITUDE => ['nullable', 'numeric', 'between:-90,90'],
            Event::LONGITUDE => ['nullable', 'numeric', 'between:-180,180'],

            'organizations.*.' . EventOrganization::CAN_ATTEND => ['boolean'],
            'organizations.*.' . EventOrganization::TROOPERS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
            'organizations.*.' . EventOrganization::HANDLERS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
        ];
    }
}