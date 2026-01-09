<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Rules\Admin\Organizations\UniqueCostumeNameRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating an existing organization's costumes.
 *
 * This class defines validation rules for updating organization costume information.
 * It ensures the costume name remains unique among sibling costumes
 * (children of the same organization) while excluding the costume being updated
 * from the uniqueness check.
 */
class UpdateCostumesRequest extends FormRequest
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

        if ($organization === null)
        {
            throw new AuthorizationException('Organization not found or unauthorized.');
        }

        return $this->user()->can('update', $organization);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the costume name ensuring it's unique among sibling costumes,
     * excluding the current costume from the uniqueness check.
     *
     * @return array<string, mixed> The validation rules for updating an organization's costumes.
     */
    public function rules(): array
    {
        $organization = $this->route('organization');

        $rules = [
            'costumes.*.name' => [
                'required',
                'string',
                'max:128',
                new UniqueCostumeNameRule($organization),
            ],
        ];

        return $rules;
    }
}