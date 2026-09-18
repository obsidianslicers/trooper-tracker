<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use App\Rules\Troopers\VisitorOrganizationRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating a trooper's organization memberships.
 *
 * This class defines validation rules for managing a trooper's organization memberships
 * using a popup picker to select specific organizations. The validation ensures:
 * - Organization IDs are required when an identifier is provided
 * - Selected organizations must exist and be descendants of the selected organization tree
 * - Identifiers follow organization-specific validation rules when provided
 *
 * Administrators and moderators can modify trooper membership settings.
 *
 */
class ApproveTrooperMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * Verifies that the trooper exists in the route and that the authenticated
     * user is an administrator or moderator.
     *
     * @return bool Returns true if the user is an administrator or moderator
     *
     * @throws AuthorizationException If the trooper is not found in the route
     */
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->can('approve', $trooper);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Generates dynamic validation rules for organization memberships.
     *
     * @return array<string, mixed> The validation rules for organization memberships
     */
    public function rules(): array
    {
        return [
            'trooper' => [
                function ($attribute, $value, $fail)
                {
                    $trooper = $this->route('trooper');

                    if ($trooper->membership_status !== MembershipStatus::PENDING)
                    {
                        $fail('The trooper must have a pending membership status.');
                    }
                },
            ],
        ];
    }
}
