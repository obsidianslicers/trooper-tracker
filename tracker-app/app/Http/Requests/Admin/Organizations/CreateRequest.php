<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Organizations;

use App\Models\Organization;
use App\Rules\Admin\Organizations\UniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for creating a new organization.
 *
 * This class defines validation rules for creating child organizations under a parent
 * organization. It ensures the organization name is unique among siblings within the
 * same parent organization using a custom validation rule.
 */
class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true if the user has permission to create organizations.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Organization::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the organization name ensuring it's unique among sibling organizations
     * under the same parent organization.
     *
     * @return array<string, mixed> The validation rules for creating an organization.
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:64',
                new UniqueNameRule(false, $this->route('parent'))
            ],
        ];

        return $rules;
    }
}