<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Organization;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for the update organization notifications form.

 * This class defines validation rules for updating a trooper's organization notifications settings.
 */
class UpdateOrganizationNotificationsRequest extends FormRequest
{
    use HasNormalizers;

    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true as any authenticated user can update their profile
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed> The validation rules for the profile update form
     */
    public function rules(): array
    {
        $rules = [
            'organization_ids' => [
                'required',
                'array',
            ],
            'organization_ids.*' => [
                'required',
                'integer',
                Rule::exists(Organization::class, Organization::ID),
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
        ];

        return $rules;
    }
}
