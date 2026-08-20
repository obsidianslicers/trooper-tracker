<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Models\Costume;
use App\Models\OrganizationCostume;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for the add costume form.
 *
 * This class defines validation rules for adding a costume to a trooper's profile.
 */
class AddCostumeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true as any authenticated user can add a costume to their profile.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed> The validation rules for adding a costume to the profile
     */
    public function rules(): array
    {
        $rules = [
            'costume_id' => [
                'required',
                'integer',
                Rule::exists(Costume::class, Costume::ID),
            ],
            'organization_ids' => [
                'required',
                'array',
            ],
            'organization_ids.*' => [
                'required',
                'integer',
                Rule::exists(OrganizationCostume::class, OrganizationCostume::ORGANIZATION_ID)
                    ->where(OrganizationCostume::COSTUME_ID, $this->input('costume_id')),
            ],
        ];

        return $rules;
    }
}
