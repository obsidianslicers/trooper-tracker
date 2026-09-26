<?php

declare(strict_types=1);

namespace App\Rules\Admin\Troopers;

use App\Enums\TrooperRequestStatus;
use App\Models\TrooperRequest;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validation rule requiring the trooper request to have a pending status.
 */
class PendingTrooperRequest implements ValidationRule
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
        $trooper_request = $value instanceof TrooperRequest ? $value : TrooperRequest::find($value);

        if ($trooper_request)
        {
            if ($trooper_request->status !== TrooperRequestStatus::PENDING)
            {
                $fail('The trooper request must have a pending status.');
            }
        }
        else
        {
            $fail('The specified trooper request could not be found.');
        }
    }
}
