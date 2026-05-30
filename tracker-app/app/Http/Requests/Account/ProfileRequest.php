<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\TrooperTheme;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for the user profile update form.
 *
 * This class defines validation rules for updating a trooper's profile information,
 * including name, email, phone, and theme preferences. The phone number is sanitized
 * during validation preparation to ensure consistent formatting.
 */
class ProfileRequest extends FormRequest
{
    use HasNormalizers;

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
            Trooper::LEGAL_NAME => ['required', 'string', 'max:256'],
            Trooper::DISPLAY_NAME => ['required', 'string', 'max:256'],
            Trooper::PHONE => ['nullable', 'string', 'max:16'],
            Trooper::THEME => ['required', 'string', 'max:16', 'in:'.TrooperTheme::toValidator()],
            Trooper::FORUM_DISPLAY_COSTUME_ID => [
                'nullable',
                'integer',
                Rule::exists('tt_trooper_costumes', 'id')
                    ->where('trooper_id', $this->user()?->id ?? 0)
                    ->whereNull('deleted_at'),
            ],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation
     *
     * This method sanitizes the phone number by removing any non-digit characters.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && !empty($this->input('phone')))
        {
            $phone = $this->normalizePhoneInput($this->input('phone'));

            $this->merge(['phone' => $phone]);
        }
    }
}
