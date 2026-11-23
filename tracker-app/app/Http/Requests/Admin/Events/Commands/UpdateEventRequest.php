<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events\Commands;

use App\Http\Requests\Admin\Events\CommonRules;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
 * Uses CommonRules trait for shared validation rules with StoreEventRequest.
 */
class UpdateEventRequest extends FormRequest
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
        $event = $this->route('event');

        return [
            ...$this->getCommonRules(),
            //  the event may stay with its host even when this trooper can't host for it (they
            //  were picked as its coordinator), but only moves to an organization they can host for
            Event::ORGANIZATION_ID => [
                'required',
                Rule::in([
                    $event->organization_id,
                    ...Organization::eventHostableBy($this->user())->pluck(Organization::ID)->all(),
                ]),
            ],
            //  only someone going or attended can be picked; empty means the creator
            Event::COORDINATOR_TROOPER_ID => [
                'nullable',
                'integer',
                Rule::in($event->coordinatorCandidates()->pluck(Trooper::ID)->all()),
            ],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Event::TROOPERS_ALLOWED.'.required_if' => __('admin.The troopers allowed field is required when limit organizations is set to Yes.'),
            Event::HANDLERS_ALLOWED.'.required_if' => __('admin.The handlers allowed field is required when limit organizations is set to Yes.'),
            Event::COORDINATOR_TROOPER_ID.'.in' => __('admin.The event coordinator must be an event coordinator or command staff member who is going to or attended the event.'),
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
