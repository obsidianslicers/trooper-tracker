<?php

declare(strict_types=1);

namespace App\Rules\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that members (non-visitors) cannot be assigned to top-level organizations (depth=1).
 *
 * Passes without checking when the trooper is a visitor.
 */
class MemberOrganizationRule implements ValidationRule
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->trooper->is_visitor)
        {
            return;
        }

        $org = Organization::find($value);

        if ($org && $org->organizations()->count() !== 0)
        {
            $fail('Members must join at the lowest-level organization.');
        }
    }
}
