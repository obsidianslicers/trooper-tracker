<?php

namespace App\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Admin\Troopers\OrganizationLeafNodeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Validates profile setup organization selection and hierarchical assignments.
 *
 * Generates dynamic validation rules for each organization, ensuring regions and units
 * are properly selected when an organization is chosen. Fetches active organizations
 * via `Organization::fullyLoaded()` and constructs rules for region and unit fields.
 *
 * Key behaviors:
 * - `prepareForValidation()` sanitizes phone numbers by removing non-digit characters.
 * - `withValidator()` attaches custom, user-facing error messages for organization rules.
 * - `getOrganizations()` caches organizations for efficient repeated access.
 *
 * @package App\Http\Requests\Account
 * @property \Illuminate\Support\Collection|null $organizations Cached organizations for rule generation
 */
class NotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true as registration is open to guests.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Combines base email rules with dynamically generated organization hierarchy rules.
     *
     * @return array<string, mixed> The combined validation rules for the setup form.
     */
    public function rules(): array
    {
        $rules = [
            Trooper::NOTIFICATION_FREQUENCY => [
                'required',
                'in:' . NotificationFrequency::toValidator(),
            ],
            'organizations.*.can_notify' => ['boolean']
        ];

        return $rules;
    }
}