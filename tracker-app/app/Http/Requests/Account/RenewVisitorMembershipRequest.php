<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\TrooperTheme;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use App\Rules\Account\ExpiredVisitorMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for the renew visitor membership form.
 *
 * This class defines validation rules for renewing a trooper's visitor membership,
 * ensuring that the trooper has an expired visitor membership before allowing renewal.
 */
class RenewVisitorMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true as any authenticated user can update their profile
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed> The validation rules for the profile update form
     */
    public function rules(): array
    {
        $rules = [
            'trooper' => [
                new ExpiredVisitorMembership()
            ]
        ];

        return $rules;
    }

    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'trooper' => $this->user()
        ]);
    }
}
