<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Event;
use App\Models\EventOrganization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation and authorization for updating an existing Event.
 *
 * This FormRequest class validates event update requests and ensures proper authorization.
 * It validates all event fields including:
 * - Event details (name, status, dates, contact info, website)
 * - Venue information (address, city, state, zip, country)
 * - Charity information (name, hours, direct/indirect funds, notes)
 * - Capacity limits (troopers, handlers, friends allowed, tentative signups)
 * - Event features (secure staging, blasters, props, parking, accessibility, amenities)
 * - Event specifics (expected attendees, requested characters)
 * - Organization associations (attendance permissions and limits per organization)
 * - Geographic coordinates (latitude/longitude)
 * - Miscellaneous fields (comments, referred by, source)
 *
 * Uses CommonRules trait for shared validation rules with CreateRequest.
 */
class UpdateRequest extends FormRequest
{
    use CommonRules;
    use HasNormalizers;

    /**
     * Determine if the user is authorized to make this request
     *
     * Checks if the user has permission to update the event specified in the route.
     *
     * @throws AuthorizationException if the event is not found
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event === null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed> The validation rules for the request
     */
    public function rules(): array
    {
        return $this->getCommonRules();
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Event::TROOPERS_ALLOWED.'.required_if' => 'The troopers allowed field is required when limit organizations is set to Yes.',
            Event::HANDLERS_ALLOWED.'.required_if' => 'The handlers allowed field is required when limit organizations is set to Yes.',
        ];
    }

    /**
     * Prepare the data for validation
     *
     * This method ensures that each organization in the input array has a 'can_attend' attribute, defaulting to false if it is not present.
     */
    protected function prepareForValidation(): void
    {
        $organizations = $this->normalizeBooleanFields(
            $this->input('organizations', []),
            [EventOrganization::CAN_ATTEND],
            true
        );

        foreach ($organizations as $organization_id => $organization)
        {
            if (!$organizations[$organization_id][EventOrganization::CAN_ATTEND])
            {
                $organizations[$organization_id][EventOrganization::TROOPERS_ALLOWED] = null;
                $organizations[$organization_id][EventOrganization::HANDLERS_ALLOWED] = null;
            }
        }

        $this->merge(['organizations' => $organizations]);
    }
}
