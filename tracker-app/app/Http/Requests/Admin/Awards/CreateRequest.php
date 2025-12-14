<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Models\Award;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for creating a new award.
 *
 * This class defines validation rules for creating awards, ensuring that the award
 * name, frequency, and organization are properly validated. The organization must be
 * one that the authenticated moderator has permission to manage.
 */
class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true if the user has permission to create awards.
     */
    public function authorize(): bool
    {
        //  we check NULL or PICKED in the rules
        return $this->user()->can('create', Award::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the award name, frequency enum value, and ensures the organization
     * is one that the authenticated moderator has permission to manage.
     *
     * @return array<string, mixed> The validation rules for creating an award.
     */
    public function rules(): array
    {
        $rules = [
            Award::NAME => [
                'required',
                'string',
                'max:128',
            ],
            Award::FREQUENCY => [
                'required',
                'string',
                'max:16',
                'in:' . AwardFrequency::toValidator()
            ],
        ];

        $trooper = $this->user();

        $rules[Award::ORGANIZATION_ID] = [
            'required',
            Rule::exists(Organization::class, Organization::ID)
                ->whereIn('id', Organization::moderatedBy($trooper)->pluck('id')),
        ];

        return $rules;
    }
}