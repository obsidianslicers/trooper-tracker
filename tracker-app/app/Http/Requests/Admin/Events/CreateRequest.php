<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for the user registration form.
 *
 * This class defines the base validation rules for user registration and dynamically
 * adds rules based on the organizations a user selects, including custom rules for
 * organization-specific identifiers and unit selections. It also customizes error messages
 * for a better user experience.
 */
class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true as registration is open to guests.
     */
    public function authorize(): bool
    {
        //  we check NULL or PICKED in the rules
        return $this->user()->can('create', Event::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed> The combined validation rules for the registration form.
     */
    public function rules(): array
    {
        $rules = [
            Event::NAME => [
                'required',
                'string',
                'max:128',
            ],
            Event::STARTS_AT => ['required', 'date'],
            Event::ENDS_AT => ['required', 'date', 'after:starts_at'],
        ];

        $trooper = $this->user();

        // Admins can either leave it null or pick any existing org
        $rules[Event::ORGANIZATION_ID] = [
            'required',
            Rule::exists(Organization::class, Organization::ID)
        ];

        return $rules;
    }
}