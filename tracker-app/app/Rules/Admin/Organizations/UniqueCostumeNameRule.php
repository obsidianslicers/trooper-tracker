<?php

declare(strict_types=1);

namespace App\Rules\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a costume name is unique within a specific organization.
 *
 * This rule ensures costume names don't conflict with other costumes
 * in the same organization. When updating an existing costume, it can
 * exclude the current costume from the uniqueness check by extracting
 * the costume ID from the attribute name.
 */
class UniqueCostumeNameRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param Organization $organization The organization to check uniqueness within
     */
    public function __construct(
        private readonly Organization $organization
    ) {
    }

    /**
     * Run the validation rule.
     *
     * Checks if the costume name already exists within the organization.
     * Extracts the costume ID from the attribute name (e.g., "costumes.123.name" -> 123)
     * and uses it to exclude that costume from the uniqueness check.
     *
     * @param string $attribute The name of the attribute being validated
     * @param mixed $value The value of the attribute being validated
     * @param Closure $fail The failure callback
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Extract the costume ID from the attribute (e.g., "costumes.123.name" -> 123)
        $ignore_costume_id = null;
        if (preg_match('/costumes\.(\d+)\.name/', $attribute, $matches))
        {
            $ignore_costume_id = (int) $matches[1];
        }

        $query = OrganizationCostume::where(OrganizationCostume::ORGANIZATION_ID, $this->organization->id)
            ->where(OrganizationCostume::NAME, $value);

        if ($ignore_costume_id !== null)
        {
            $query->where(OrganizationCostume::ID, '!=', $ignore_costume_id);
        }

        if ($query->exists())
        {
            $fail('The costume name must be unique within this organization.');
        }
    }
}
