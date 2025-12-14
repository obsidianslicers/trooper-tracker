<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Notices;

use App\Enums\NoticeType;
use App\Models\Notice;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for creating a new notice.
 *
 * This class defines validation rules for creating notices, ensuring that the notice
 * title, message, type, and date range are properly validated. The organization 
 * assignment rules vary based on the user's role: administrators can create global 
 * notices (no organization) or organization-specific notices, while moderators must 
 * assign the notice to an organization they moderate.
 */
class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true if the user has permission to create notices.
     */
    public function authorize(): bool
    {
        //  we check NULL or PICKED in the rules
        return $this->user()->can('create', Notice::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the notice title, message, type enum value, date range, and organization.
     * Administrators can optionally assign an organization or leave it null for global notices.
     * Moderators must assign an organization they have permission to manage.
     *
     * @return array<string, mixed> The validation rules for creating a notice.
     */
    public function rules(): array
    {
        $rules = [
            Notice::TITLE => [
                'required',
                'string',
                'max:128',
            ],
            Notice::MESSAGE => [
                'required',
                'string',
            ],
            Notice::TYPE => [
                'required',
                'string',
                'max:16',
                'in:' . NoticeType::toValidator()
            ],
            Notice::STARTS_AT => ['required', 'date'],
            Notice::ENDS_AT => ['required', 'date', 'after:starts_at'],
        ];

        $trooper = $this->user();

        // Organization rules depend on role
        if ($trooper->isAdministrator())
        {
            // Admins can either leave it null or pick any existing org
            $rules[Notice::ORGANIZATION_ID] = [
                'nullable',
                Rule::exists(Organization::class, Organization::ID)
            ];
        }
        else
        {
            // Non-admins must supply an org they moderate
            $rules[Notice::ORGANIZATION_ID] = [
                'required',
                Rule::exists(Organization::class, Organization::ID)
                    ->whereIn('id', Organization::moderatedBy($trooper)->pluck('id')),
            ];
        }


        return $rules;
    }
}