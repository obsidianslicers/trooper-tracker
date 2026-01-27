<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates trooper notification settings and per-organization notification preferences.
 *
 * This request validates the trooper's global notification frequency preference and
 * per-organization notification settings (should_notify flags). The validation ensures:
 * - notification_frequency is a valid NotificationFrequency enum value
 * - organizations array contains boolean should_notify values for each organization
 *
 * @package App\Http\Requests\Account
 */
class NotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true, allowing authenticated troopers to update their notification settings.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates:
     * - notification_frequency: Required, must be a valid NotificationFrequency enum value
     * - organizations.*.should_notify: Optional boolean for each organization's notification preference
     *
     * @return array<string, mixed> The validation rules for notification settings.
     */
    public function rules(): array
    {
        $rules = [
            Trooper::NOTIFICATION_FREQUENCY => [
                'required',
                'in:' . NotificationFrequency::toValidator(),
            ],
            'organizations.*.should_notify' => ['boolean']
        ];

        return $rules;
    }
}