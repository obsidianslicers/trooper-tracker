<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for the update push notifications form.

 * This class defines validation rules for updating a trooper's push notifications settings.
 */
class UpdatePushNotificationsRequest extends FormRequest
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
            Trooper::PUSH_NOTIFICATIONS_ENABLED => [
                'required',
                'boolean',
            ],
        ];

        return $rules;
    }
}
