<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates trooper profile setup fields (email, legal name, theme, notification frequency).
 */
class SetupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true as registration is open to guests
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            Trooper::LEGAL_NAME => ['required', 'string', 'max:256'],
            Trooper::EMAIL => [
                'required',
                'string',
                'email:rfc',
                'max:256',
                Rule::unique(Trooper::class, Trooper::EMAIL)
                    ->ignore($this->user()->id, Trooper::ID),
            ],
            Trooper::THEME => [
                'required',
                'string',
                'max:16',
                TrooperTheme::toValidator(),
            ],
            Trooper::NOTIFICATION_FREQUENCY => [
                'required',
                'string',
                'max:16',
                'in:'.NotificationFrequency::toValidator(),
            ],
        ];
    }
}
