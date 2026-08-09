<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for the update notification frequency form.
 *
 * This class defines validation rules for updating a trooper's notification frequency,
 * including how often they want to receive notifications. The phone number is sanitized
 * during validation preparation to ensure consistent formatting.
 */
class UpdateNotificationFrequencyRequest extends FormRequest
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
            Trooper::NOTIFICATION_FREQUENCY => [
                'required',
                'string',
                'max:16',
                NotificationFrequency::toValidator(),
            ],
        ];

        return $rules;
    }
}
