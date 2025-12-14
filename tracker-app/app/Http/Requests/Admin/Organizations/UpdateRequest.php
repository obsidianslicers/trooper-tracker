<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Organizations;

use App\Rules\Admin\Organizations\UniqueNameRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator as ValidatorInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an existing organization.
 *
 * This class defines validation rules for updating organization information.
 * It ensures the organization name remains unique among sibling organizations
 * (children of the same parent) while excluding the organization being updated
 * from the uniqueness check.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Verifies that the organization exists in the route and that the authenticated
     * user has permission to update it.
     *
     * @return bool Returns true if the user can update the organization.
     * @throws AuthorizationException if the organization is not found in the route.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        if ($organization == null)
        {
            throw new AuthorizationException('Organization not found or unauthorized.');
        }

        return $this->user()->can('update', $organization);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the organization name ensuring it's unique among sibling organizations,
     * excluding the current organization from the uniqueness check.
     *
     * @return array<string, mixed> The validation rules for updating an organization.
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:64',
                new UniqueNameRule(true, $this->route('organization'))
            ],
        ];

        return $rules;
    }
}