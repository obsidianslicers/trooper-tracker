<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Awards;

use App\Models\AwardTrooper;
use App\Models\Trooper;
use App\Rules\Admin\Account\AwardDateMatchesFrequencyRule;
use App\Rules\Admin\Account\TrooperNotAlreadyAwardedRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for assigning an award to a single trooper.
 *
 * Validates that the trooper exists, the award date matches the award's frequency
 * schedule, and that the trooper is not already awarded on the specified date.
 */
class AssignTrooperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true if the user has permission to update the award
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('award'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the trooper ID and award date, ensuring the date matches the
     * award's frequency schedule and the trooper is not already awarded.
     *
     * @return array<string, mixed> The validation rules for assigning an award
     */
    public function rules(): array
    {
        $award = $this->route('award');
        $award_date = $this->input(AwardTrooper::AWARD_DATE);

        $trooper_rules = [
            'required',
            'integer',
            'exists:'.Trooper::class.','.Trooper::ID,
        ];

        // Only add duplicate check rule if award_date is provided
        if ($award_date !== null)
        {
            $trooper_rules[] = new TrooperNotAlreadyAwardedRule($award->id, $award_date);
        }

        $award_date_rules = [
            'required',
            'date',
            new AwardDateMatchesFrequencyRule($award->frequency),
        ];

        return [
            AwardTrooper::TROOPER_ID => $trooper_rules,
            AwardTrooper::AWARD_DATE => $award_date_rules,
        ];
    }
}
