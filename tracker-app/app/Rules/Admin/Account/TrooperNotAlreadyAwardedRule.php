<?php

declare(strict_types=1);

namespace App\Rules\Admin\Account;

use App\Models\AwardTrooper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a trooper is not already awarded for the specified date.
 *
 * Ensures that a trooper cannot receive the same award twice on the same date,
 * preventing duplicate award assignments.
 */
class TrooperNotAlreadyAwardedRule implements ValidationRule
{
    /**
     * Initialize the rule with the award ID and date.
     *
     * @param  int  $award_id  The award ID to validate against.
     * @param  string  $award_date  The award date to validate against.
     */
    public function __construct(
        private readonly int $award_id,
        private readonly string $award_date
    ) {}

    /**
     * Validate that the trooper is not already awarded on the specified date.
     *
     * @param  string  $attribute  The attribute name being validated.
     * @param  mixed  $value  The trooper ID value to validate.
     * @param  Closure  $fail  The closure to call when validation fails.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = AwardTrooper::query()
            ->where(AwardTrooper::AWARD_ID, $this->award_id)
            ->where(AwardTrooper::AWARD_DATE, $this->award_date)
            ->where(AwardTrooper::TROOPER_ID, $value)
            ->exists();

        if ($exists)
        {
            $fail('This trooper already has this award for the selected date.');
        }
    }
}
