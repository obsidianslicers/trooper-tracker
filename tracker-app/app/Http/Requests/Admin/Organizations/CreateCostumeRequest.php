<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Organizations;

use App\Rules\Admin\Organizations\UniqueCostumeNameRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for creating a new costume.
 *
 * This class defines validation rules for creating costumes under a parent
 * organization. It ensures the costume name is unique among siblings within the
 * same parent organization using a custom validation rule.
 */
class CreateCostumeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true if the user has permission to create costumes.
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
     * Validates the costume name ensuring it's unique among sibling costumes
     * under the same parent organization.
     *
     * @return array<string, mixed> The validation rules for creating a costume.
     */
    public function rules(): array
    {
        $organization = $this->route('organization');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:128',
                new UniqueCostumeNameRule($organization),
            ],
        ];

        return $rules;
    }
}
