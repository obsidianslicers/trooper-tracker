<?php

declare(strict_types=1);

namespace App\Rules\Admin\Troopers;

use App\Models\Trooper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validation rule requiring a valid email address, unless the value is unchanged from the trooper's
 * current stored email.
 *
 * Legacy troopers imported without a real email address were assigned a placeholder value that
 * never passes email-format validation. Skipping the format check when the submitted email is
 * unchanged lets admins edit other profile fields without being forced to fix a legacy placeholder
 * first.
 */
class ValidTrooperEmailRule implements ValidationRule
{
    /**
     * Creates a new rule instance.
     *
     * @param  Trooper|null  $trooper  The trooper being validated, if any.
     */
    public function __construct(private readonly ?Trooper $trooper = null) {}

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  mixed  $value  The value of the attribute being validated.
     * @param  Closure(string): PotentiallyTranslatedString  $fail  The closure to call on validation failure.
     * @return void This rule never returns a value; it only triggers $fail().
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->trooper !== null && $value === $this->trooper->email)
        {
            return;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL))
        {
            $fail("The {$attribute} field must be a valid email address.");
        }
    }
}
