<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an existing Event.
 */
class UpdateRequest extends FormRequest
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
            Event::NAME => ['required', 'string', 'max:128',],
            Event::STATUS => ['required', 'string', 'max:16', 'in:' . EventStatus::toValidator()],
            Event::TROOPERS_ALLOWED => ['nullable', 'integer', 'between:1,99999'],
            Event::HANDLERS_ALLOWED => ['nullable', 'integer', 'between:0,99999'],
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
            Event::REQUESTED_CHARACTERS => ['nullable', 'integer', 'min:0'],
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

            'organizations.*.can_attend' => ['boolean'],
        ];

        return $rules;
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Event::TROOPERS_ALLOWED . '.required_if' =>
                'The troopers allowed field is required when limit organizations is set to Yes.',
            Event::HANDLERS_ALLOWED . '.required_if' =>
                'The handlers allowed field is required when limit organizations is set to Yes.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * This method ensures that each organization in the input array has a 'can_attend' attribute, defaulting to false if it is not present.
     */
    protected function prepareForValidation(): void
    {
        $organizations = $this->input('organizations', []);

        foreach ($organizations as $key => $org)
        {
            // If 'can_attend' is missing, default to false
            $can_attend = $org['can_attend'] ?? false;

            // Coerce to boolean (handles "on", "1", "true", etc.)
            $organizations[$key]['can_attend'] = filter_var($can_attend, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge(['organizations' => $organizations]);
    }
}