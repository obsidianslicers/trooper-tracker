<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizationType;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Handles the validation for updating the organizations associated with an Event.
 */
class UpdateOrganizationsRequest extends FormRequest
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
            Event::TROOPERS_ALLOWED => ['required', 'integer', 'between:1,99999'],
            Event::HANDLERS_ALLOWED => ['required', 'integer', 'between:0,99999'],
            // Each organization row
            'organizations' => ['array'], // ensures it's an array
            'organizations.*.' . EventOrganization::CAN_ATTEND => ['required', 'boolean'],
            // 'organizations.*.' . EventOrganization::TROOPERS_ALLOWED => [
            //     'nullable',
            //     'integer',
            //     'between:0,99999',
            // ],
            // 'organizations.*.' . EventOrganization::HANDLERS_ALLOWED => [
            //     'nullable',
            //     'integer',
            //     'between:0,99999',
            // ],
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
        $input_organizations = $this->input('organizations', []);

        foreach ($input_organizations as $key => $org)
        {
            // If can_attend is missing, set it to false
            if (!array_key_exists(EventOrganization::CAN_ATTEND, $org))
            {
                $input_organizations[$key][EventOrganization::CAN_ATTEND] = false;
            }
        }

        $this->merge(['organizations' => $input_organizations]);
    }

    // public function messages(): array
    // {
    //     return [
    //         'organizations.*.' . EventOrganization::TROOPERS_ALLOWED . '.integer' =>
    //             'Must be an integer.',
    //         'organizations.*.' . EventOrganization::HANDLERS_ALLOWED . '.integer' =>
    //             'Must be an integer.',
    //     ];
    // }

    // protected function prepareForValidation(): void
    // {
    //     $input_organizations = $this->input('organizations', []);

    //     foreach ($input_organizations as $key => $org)
    //     {
    //         // If can_attend is missing, set it to false
    //         if (!array_key_exists(EventOrganization::CAN_ATTEND, $org))
    //         {
    //             $input_organizations[$key][EventOrganization::CAN_ATTEND] = false;
    //         }
    //     }

    //     $updated = $this->normalizeLimitInputs($input_organizations);

    //     $this->merge(['organizations' => $updated]);
    // }

    // private function normalizeLimitInputs(array $input_organizations): array
    // {
    //     $organizations = Organization::all();

    //     $this->loadOrganizations($organizations, $input_organizations);
    //     $this->summarizeOrganizations($organizations, OrganizationType::UNIT);
    //     $this->summarizeOrganizations($organizations, OrganizationType::REGION);
    //     $this->summarizeOrganizations($organizations, OrganizationType::ORGANIZATION);

    //     foreach ($organizations as $organization)
    //     {
    //         $input_organizations[$organization->id][EventOrganization::TROOPERS_ALLOWED] = $organization->troopers_allowed;
    //         $input_organizations[$organization->id][EventOrganization::HANDLERS_ALLOWED] = $organization->handlers_allowed;
    //     }

    //     return $input_organizations;
    // }

    // private function loadOrganizations(Collection $organizations, array $input_organizations)
    // {
    //     foreach ($organizations as $organization)
    //     {
    //         $limits = $input_organizations[$organization->id];

    //         $organization->troopers_allowed = $limits[EventOrganization::TROOPERS_ALLOWED] ?? null;
    //         $organization->handlers_allowed = $limits[EventOrganization::HANDLERS_ALLOWED] ?? null;
    //     }
    // }

    // private function summarizeOrganizations(Collection $organizations, OrganizationType $type)
    // {
    //     // Find all descendants whose node_path starts with this org's node_path + "."
    //     $children = $organizations->filter(function ($child) use ($type)
    //     {
    //         return $child->type == $type;
    //     });

    //     foreach ($children as $organization)
    //     {
    //         $parent_id = $organization->id;

    //         $troopers_allowed = $this->summarizeOrganization($organizations, $parent_id, EventOrganization::TROOPERS_ALLOWED);
    //         $handlers_allowed = $this->summarizeOrganization($organizations, $parent_id, EventOrganization::HANDLERS_ALLOWED);

    //         $organization->troopers_allowed = max($troopers_allowed, $organization->troopers_allowed);
    //         $organization->handlers_allowed = max($handlers_allowed, $organization->handlers_allowed);
    //     }
    // }

    // private function summarizeOrganization(Collection $organizations, ?int $parent_id, string $field): ?int
    // {
    //     $children = $organizations->filter(function ($child) use ($parent_id)
    //     {
    //         return $child->parent_id == $parent_id;
    //     });

    //     if ($children->every(fn($c) => $c->{$field} == null))
    //     {
    //         return null;
    //     }

    //     return $children->sum(fn($c) => $c->{$field} ?? 0);
    // }

    // public function withValidator($validator): void
    // {
    //     $validator->after(function ($validator)
    //     {
    //         $troopers_allowed = $this->input('troopers_allowed');
    //         $handlers_allowed = $this->input('handlers_allowed');
    //         $troopers_allowed_children = $this->summarizeOrganization($organizations, null, EventOrganization::TROOPERS_ALLOWED);
    //         $handlers_allowed_children = $this->summarizeOrganization($organizations, null, EventOrganization::HANDLERS_ALLOWED);

    //         if ($troopers_allowed_children != null && $troopers_allowed != $troopers_allowed_children)
    //         {
    //             $validator->errors()->add(
    //                 Event::TROOPERS_ALLOWED,
    //                 "Troopers allowed mismatch: event={$troopers_allowed}, organizations={$troopers_allowed_children}."
    //             );
    //         }
    //         if ($handlers_allowed_children != null && $handlers_allowed != $handlers_allowed_children)
    //         {
    //             $validator->errors()->add(
    //                 Event::HANDLERS_ALLOWED,
    //                 "Handlers allowed mismatch: event={$handlers_allowed}, organizations={$handlers_allowed_children}."
    //             );
    //         }
    //     });
    // }
}