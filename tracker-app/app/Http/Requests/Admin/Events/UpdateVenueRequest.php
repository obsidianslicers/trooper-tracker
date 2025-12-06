<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventVenue;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an existing Event.
 */
class UpdateVenueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Checks if the user has permission to update the event specified in the route.
     *
     * @return bool
     * @throws AuthorizationException if the event is not found.
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event == null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed> The validation rules for the request.
     */
    public function rules(): array
    {
        $rules = [
            EventVenue::CONTACT_NAME => ['nullable', 'string', 'max:128'],
            EventVenue::CONTACT_PHONE => ['nullable', 'string', 'max:128'],
            EventVenue::CONTACT_EMAIL => ['nullable', 'email', 'max:128'],

            EventVenue::VENUE => ['nullable', 'string', 'max:256'],
            EventVenue::VENUE_ADDRESS => ['nullable', 'string', 'max:256'],
            EventVenue::VENUE_CITY => ['nullable', 'string', 'max:128'],
            EventVenue::VENUE_STATE => ['nullable', 'string', 'max:128'],
            EventVenue::VENUE_ZIP => ['nullable', 'string', 'max:128'],
            EventVenue::VENUE_COUNTRY => ['nullable', 'string', 'max:128'],

            EventVenue::EVENT_START => ['required', 'date'],
            EventVenue::EVENT_END => ['required', 'date', 'after:' . EventVenue::EVENT_START],
            EventVenue::EVENT_WEBSITE => ['nullable', 'string', 'max:512'],

            EventVenue::EXPECTED_ATTENDEES => ['nullable', 'integer', 'min:0'],
            EventVenue::REQUESTED_CHARACTERS => ['nullable', 'integer', 'min:0'],
            EventVenue::REQUESTED_CHARACTER_TYPES => ['nullable', 'string'],

            EventVenue::SECURE_STAGING_AREA => ['boolean'],
            EventVenue::ALLOW_BLASTERS => ['boolean'],
            EventVenue::ALLOW_PROPS => ['boolean'],
            EventVenue::PARKING_AVAILABLE => ['boolean'],
            EventVenue::ACCESSIBLE => ['boolean'],

            EventVenue::AMENITIES => ['nullable', 'string'],
            EventVenue::COMMENTS => ['nullable', 'string'],
            EventVenue::REFERRED_BY => ['nullable', 'string', 'max:1024'],
            EventVenue::SOURCE => ['nullable', 'string'],
            EventVenue::LATITUDE => ['nullable', 'numeric', 'between:-90,90'],
            EventVenue::LONGITUDE => ['nullable', 'numeric', 'between:-180,180'],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * This method ensures that each organization in the input array has a 'can_attend' attribute, defaulting to false if it is not present.
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        $boolean_fields = [
            EventVenue::SECURE_STAGING_AREA,
            EventVenue::ALLOW_BLASTERS,
            EventVenue::ALLOW_PROPS,
            EventVenue::PARKING_AVAILABLE,
            EventVenue::ACCESSIBLE,
        ];

        foreach ($boolean_fields as $field)
        {
            if (!array_key_exists($field, $input))
            {
                $input[$field] = false;
            }
        }

        $this->replace($input);
    }
}