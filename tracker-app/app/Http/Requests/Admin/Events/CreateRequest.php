<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates and authorizes event creation requests for administrators and moderators.
 *
 * This FormRequest class handles validation and authorization for creating new events.
 * It ensures that:
 * - Only users with event creation permission can proceed (via EventPolicy)
 * - The organization_id belongs to an organization the user moderates
 * - All required event fields meet validation criteria (defined in CommonRules trait)
 *
 * Authorization logic:
 * - Administrators can create events for any organization
 * - Moderators can only create events for organizations they moderate
 *
 * Validation includes:
 * - Event details (name, status, dates, contact info, website)
 * - Venue information (address, city, state, zip, country)
 * - Charity information (name, hours, direct/indirect funds, notes)
 * - Capacity limits (troopers, handlers, friends allowed, tentative signups)
 * - Event features (secure staging, blasters, props, parking, accessibility, amenities)
 * - Event specifics (expected attendees, requested characters)
 * - Organization-specific settings (via organizations array)
 * - Geographic coordinates (latitude/longitude)
 * - Miscellaneous fields (comments, referred by, source)
 *
 * Uses CommonRules trait for shared validation rules between Create and Update requests.
 */
class CreateRequest extends FormRequest
{
    use CommonRules;

    /**
     * Determine if the authenticated user is authorized to create an event
     *
     * Uses the EventPolicy to check if the user has permission to create events.
     * Typically allows administrators and moderators.
     *
     * @return bool True if the user can create events, false otherwise
     */
    public function authorize(): bool
    {
        // Uses the EventPolicy to determine if the user can create an event.
        return $this->user()->can('create', Event::class);
    }

    /**
     * Get the validation rules for event creation
     *
     * Retrieves common validation rules from the CommonRules trait and adds
     * organization_id validation to ensure the user can only create events for
     * organizations they moderate.
     *
     * For administrators: Can select any organization
     * For moderators: Can only select organizations they moderate (filtered by moderatedBy scope)
     *
     * @return array<string, mixed> The validation rules for creating an event
     */
    public function rules(): array
    {
        $rules = $this->getCommonRules();

        $trooper = $this->user();

        $rules['source'] = ['nullable', 'string'];

        $rules[Event::ORGANIZATION_ID] = [
            'required',
            Rule::exists(Organization::class, Organization::ID)
                ->whereIn('id', Organization::moderatedBy($trooper)->pluck('id')),
        ];

        return $rules;
    }
}
