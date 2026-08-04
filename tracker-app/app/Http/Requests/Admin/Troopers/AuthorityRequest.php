<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating a trooper's authority settings.
 *
 * This class defines validation rules for managing trooper authority, including
 * their membership role (member, moderator, administrator) and moderator assignments
 * for specific organizations. Only administrators can modify trooper authority settings.
 */
class AuthorityRequest extends FormRequest
{
    use HasNormalizers;

    /**
     * Determine if the user is authorized to make this request
     *
     * Verifies that the trooper exists in the route and that the authenticated
     * user is an administrator. Only administrators can modify authority settings.
     *
     * @return bool Returns true if the user is an administrator
     *
     * @throws AuthorizationException if the trooper is not found in the route
     */
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->is_administrator;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Validates the membership role enum value and moderator organization selections.
     * The membership role can be nullable and must be a valid MembershipRole enum value.
     * Moderator selections are boolean values indicating which organizations the trooper
     * moderates.
     *
     * @return array<string, mixed> The validation rules for updating trooper authority
     */
    public function rules(): array
    {
        $rules = [
            Trooper::MEMBERSHIP_ROLE => ['nullable', 'string', 'max:16', MembershipRole::toValidator()],
            'organizations' => ['array'],
            'organizations.*.'.TrooperAssignment::IS_MODERATOR => ['boolean'],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation
     *
     * Converts the 'is_moderator' values in the 'organizations' input
     * to booleans for proper validation.
     */
    protected function prepareForValidation(): void
    {
        $organizations = $this->normalizeBooleanFields(
            $this->input('organizations', []),
            [TrooperAssignment::IS_MODERATOR]
        );

        $this->merge(['organizations' => $organizations]);
    }
}
