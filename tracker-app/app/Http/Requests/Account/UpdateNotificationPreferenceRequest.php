<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\NotificationChannels;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Enums\NotificationFrequency;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for the update notification preference form.
 *
 * This class defines validation rules for updating a trooper's notification preferences,
 * including how often they want to receive notifications. The phone number is sanitized
 * during validation preparation to ensure consistent formatting.
 */
class UpdateNotificationPreferenceRequest extends FormRequest
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
        $notifications = AdministrativeNotifications::toValidator() . ',' . TrooperNotifications::toValidator();

        $notifications = str_replace('in:', '', $notifications);

        $notifications = 'in:' . $notifications;

        $rules = [
            'notification' => [
                'required',
                'string',
                'max:16',
                $notifications,
            ],
            'channel' => [
                'required',
                'string',
                'max:16',
                NotificationChannels::toValidator(),
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
        ];

        return $rules;
    }
}
