<?php

declare(strict_types=1);

namespace App\Rules\Admin\Troopers;

use App\Enums\MembershipStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Trooper;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validation rule requiring the trooper to have a pending membership status.
 */
class PendingTrooperMembership implements ValidationRule
{
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
        $trooper = $value instanceof Trooper ? $value : Trooper::find($value);

        if ($trooper)
        {
            if ($trooper->membership_status !== MembershipStatus::PENDING)
            {
                $fail('The trooper must have a pending membership status.');
            }
        }
        else
        {
            $fail('The specified trooper could not be found.');
        }
    }
}
