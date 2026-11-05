<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Organizations;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Plugins\PluginClubSynchronizers;
use App\Plugins\PluginForms;
use App\Rules\Admin\Organizations\UniqueNameRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating an existing organization.
 *
 * This class defines validation rules for updating organization information,
 * including name and, for administrators, the club's roster sync. It ensures the organization name
 * remains unique among sibling organizations (children of the same parent) while
 * excluding the organization being updated from the uniqueness check.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Verifies that the organization exists in the route and that the authenticated
     * user has permission to update it.
     *
     * @return bool Returns true if the user can update the organization
     *
     * @throws AuthorizationException if the organization is not found in the route
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
     * Validates the organization name ensuring it's unique among sibling organizations,
     * excluding the current organization from the uniqueness check.
     *
     * @return array<string, mixed> The validation rules for updating an organization
     */
    public function rules(): array
    {
        $rules = [
            Organization::NAME => [
                'required',
                'string',
                'max:64',
                new UniqueNameRule(true, $this->route('organization')),
            ],
        ];

        if ($this->canChooseSynchronizer())
        {
            $rules[Organization::SYNCHRONIZER] = [
                'nullable',
                'string',
                Rule::in($this->synchronizerKeys()),
            ];
        }

        return [...$rules, ...app(PluginForms::class)->rules(PluginForms::ORGANIZATION_UPDATE)];
    }

    /**
     * Only administrators pick a club's roster sync, and only clubs have one.
     */
    public function canChooseSynchronizer(): bool
    {
        $organization = $this->route('organization');

        return $organization?->type === OrganizationType::ORGANIZATION
            && $this->user()?->is_administrator === true;
    }

    /**
     * @return array<int, string>
     */
    private function synchronizerKeys(): array
    {
        $keys = array_keys(app(PluginClubSynchronizers::class)->options());
        $current = $this->route('organization')->synchronizer;

        return $current === null ? $keys : [...$keys, $current];
    }
}
