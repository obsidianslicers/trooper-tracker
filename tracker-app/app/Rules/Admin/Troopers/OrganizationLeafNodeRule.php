<?php

declare(strict_types=1);

namespace App\Rules\Admin\Troopers;

use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule to ensure a selected organization is a leaf node descendant of a parent organization.
 *
 * This rule validates that:
 * - The selected organization is a descendant of the specified parent organization (via node_path)
 * - The selected organization has no child organizations (is a leaf node)
 *
 * Used when assigning troopers to specific organizational units to ensure they select
 * the most specific unit available, not a parent organization with children.
 */
class OrganizationLeafNodeRule implements ValidationRule
{
    /**
     * Creates a new rule instance.
     *
     * @param Organization $organization The parent organization that the selected organization must be a descendant of.
     */
    public function __construct(private readonly Organization $organization)
    {
    }

    /**
     * Run the validation rule.
     *
     * Validates that the provided organization ID represents a leaf node descendant
     * of the parent organization.
     *
     * @param  string  $attribute The name of the attribute being validated.
     * @param  mixed  $value The organization ID being validated.
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail The closure to call on validation failure.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value)
        {
            return;
        }

        $picked_organization = Organization::find($value);

        if (!$picked_organization)
        {
            return;
        }

        // Check if picked organization is a descendant of the parent organization
        if (!str_starts_with($picked_organization->node_path, $this->organization->node_path))
        {
            $fail('The selected organization must be within ' . $this->organization->name . '.');
        }

        // Check if picked organization has no children (is a leaf node)
        if ($picked_organization->organizations()->count() > 0)
        {
            $fail('Please select a more specific unit.');
        }
    }
}
