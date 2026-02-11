<?php

declare(strict_types=1);

namespace App\Rules\Admin\Account;

use App\Enums\AwardFrequency;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that an award date matches the award's frequency schedule.
 *
 * Ensures monthly awards use the first day of the month, quarterly awards use
 * the first day of the quarter, and annual awards use January 1st. Random and
 * once-only awards accept any date.
 */
class AwardDateMatchesFrequencyRule implements ValidationRule
{
    /**
     * Initialize the rule with the award's frequency.
     *
     * @param  AwardFrequency  $frequency  The frequency schedule to validate against.
     */
    public function __construct(private readonly AwardFrequency $frequency) {}

    /**
     * Validate the award date against the frequency schedule.
     *
     * @param  string  $attribute  The attribute name being validated.
     * @param  mixed  $value  The date value to validate.
     * @param  Closure  $fail  The closure to call when validation fails.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try
        {
            $date = Carbon::parse($value);
        }
        catch (\Exception)
        {
            // Let the 'date' validator handle invalid dates
            return;
        }

        match ($this->frequency)
        {
            AwardFrequency::MONTHLY => $this->validateMonthly($date, $fail),
            AwardFrequency::QUARTERLY => $this->validateQuarterly($date, $fail),
            AwardFrequency::ANNUALLY => $this->validateAnnually($date, $fail),
            default => null, // once/random accept any date
        };
    }

    /**
     * Validate that the date is the first day of the month.
     *
     * @param  Carbon  $date  The date to validate.
     * @param  Closure  $fail  The closure to call when validation fails.
     */
    protected function validateMonthly(Carbon $date, Closure $fail): void
    {
        if (!$date->isSameDay($date->copy()->startOfMonth()))
        {
            $fail('Monthly awards must use the first day of the month.');
        }
    }

    /**
     * Validate that the date is the first day of the quarter.
     *
     * @param  Carbon  $date  The date to validate.
     * @param  Closure  $fail  The closure to call when validation fails.
     */
    protected function validateQuarterly(Carbon $date, Closure $fail): void
    {
        if (!$date->isSameDay($date->copy()->firstOfQuarter()))
        {
            $fail('Quarterly awards must use the first day of the quarter.');
        }
    }

    /**
     * Validate that the date is January 1st.
     *
     * @param  Carbon  $date  The date to validate.
     * @param  Closure  $fail  The closure to call when validation fails.
     */
    protected function validateAnnually(Carbon $date, Closure $fail): void
    {
        if (!$date->isSameDay($date->copy()->startOfYear()))
        {
            $fail('Annual awards must use January 1st.');
        }
    }
}
