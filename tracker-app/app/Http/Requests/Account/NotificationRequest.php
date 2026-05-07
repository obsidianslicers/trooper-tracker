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

        $this->merge([
            'organizations' => $organizations,
            Trooper::PUSH_NOTIFICATIONS_ENABLED => $this->boolean(Trooper::PUSH_NOTIFICATIONS_ENABLED),
        ]);
    }
}
