<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use App\Rules\Troopers\VisitorOrganizationRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the update of a single organization membership for a trooper.
 *
 * The organization comes from the route; the body carries the identifier,
 * the assignment ("Member Of") and the membership status.
 */
class UpdateMembershipRequest extends FormRequest
{
    /**
     * Only system owners, staff officers and moderators may update memberships.
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

        return $this->user()->can('moderate', $trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $trooper = $this->route('trooper');
        $organization = $this->route('organization');

        $rules = [
            'identifier' => ['nullable', 'string', 'max:64'],
            'assignment' => [
                Rule::requiredIf(fn () => !empty($this->input('identifier'))),
                'nullable',
                Rule::exists(Organization::class, Organization::ID),
                new VisitorOrganizationRule($trooper),
            ],
            'membership_status' => ['nullable', 'string', MembershipStatus::toValidator()],
        ];

        if ($organization instanceof Organization && !empty($organization->identifier_validation))
        {
            $rules['identifier'] = array_merge(
                ['nullable'],
                explode('|', $organization->identifier_validation),
                [new UniqueOrganizationIdentifierRule($organization, $trooper)]
            );
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $organization = $this->route('organization');

        if (!$organization instanceof Organization)
        {
            return [];
        }

        return [
            'identifier' => "{$organization->name} identifier",
            'assignment' => "{$organization->name} assignment",
            'membership_status' => "{$organization->name} status",
        ];
    }
}
