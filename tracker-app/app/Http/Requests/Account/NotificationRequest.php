<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates trooper notification settings and per-organization notification preferences.
 *
 * This request validates the trooper's global notification frequency preference and
 * per-organization notification settings (should_notify flags). The validation ensures:
 * - notification_frequency is a valid NotificationFrequency enum value
 * - organizations array contains boolean should_notify values for each organization
 */
class NotificationRequest extends FormRequest
{
    use HasNormalizers;

    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true, allowing authenticated troopers to update their notification settings
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Validates:
     * - notification_frequency: Required, must be a valid NotificationFrequency enum value
     * - organizations.*.should_notify: Optional boolean for each organization's notification preference
     *
     * @return array<string, mixed> The validation rules for notification settings
     */
    public function rules(): array
    {
        $rules = [
            Trooper::NOTIFICATION_FREQUENCY => [
                'required',
                'in:'.NotificationFrequency::toValidator(),
            ],
            Trooper::PUSH_NOTIFICATIONS_ENABLED => ['boolean'],
            'organizations.*.'.TrooperAssignment::SHOULD_NOTIFY => ['boolean'],
            Trooper::NOTIFICATION_PREFERENCES => ['nullable', 'array'],
            Trooper::NOTIFICATION_PREFERENCES.'.*' => ['nullable', 'array'],
            Trooper::NOTIFICATION_PREFERENCES.'.*.mail' => ['boolean'],
            Trooper::NOTIFICATION_PREFERENCES.'.*.fcm' => ['boolean'],
            Trooper::NOTIFICATION_PREFERENCES.'.*.database' => ['boolean'],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation
     *
     * Converts the 'should_notify' values in the 'organizations' input
     * to booleans for proper validation.
     */
    protected function prepareForValidation(): void
    {
        $organizations = $this->normalizeBooleanFields(
            $this->input('organizations', []),
            [TrooperAssignment::SHOULD_NOTIFY]
        );

        $raw_prefs = $this->input(Trooper::NOTIFICATION_PREFERENCES, []);
        $preferences = array_map(
            fn ($category) => is_array($category)
                ? array_map(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN), $category)
                : filter_var($category, FILTER_VALIDATE_BOOLEAN),
            $raw_prefs
        );

        $this->merge([
            'organizations' => $organizations,
            Trooper::PUSH_NOTIFICATIONS_ENABLED => $this->boolean(Trooper::PUSH_NOTIFICATIONS_ENABLED),
            Trooper::NOTIFICATION_PREFERENCES => $preferences ?: null,
        ]);
    }
}
