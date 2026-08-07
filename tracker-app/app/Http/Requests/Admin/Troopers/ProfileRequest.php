<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating a trooper's profile by an administrator.
 *
 * This class defines validation rules for administrators updating trooper profiles,
 * including name, email, phone, and membership status. Administrators can modify
 * any trooper's profile information, including approval status changes.
 */
class ProfileRequest extends FormRequest
{
    use HasNormalizers;

    /**
     * Determine if the user is authorized to make this request
     *
     * Verifies that the trooper exists in the route and that the authenticated
     * user has permission to update the trooper's profile.
     *
     * @return bool Returns true if the user can update the trooper
     *
     * @throws AuthorizationException if the trooper is not found in the route
     */
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->can('update', $trooper);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Validates the trooper's legal name, display name, email, phone, and membership status.
     * The membership status can be updated by administrators to approve or manage
     * trooper accounts.
     *
     * @return array<string, mixed> The validation rules for updating a trooper's profile
     */
    public function rules(): array
    {
        $trooper = $this->route('trooper');

        $rules = [
            Trooper::LEGAL_NAME => ['required', 'string', 'max:256'],
            Trooper::DISPLAY_NAME => ['required', 'string', 'max:256'],
            Trooper::EMAIL => $this->emailRules($trooper),
            Trooper::PHONE => ['nullable', 'string', 'max:16'],
            Trooper::MEMBERSHIP_STATUS => [
                'nullable',
                'string',
                'max:16',
                MembershipStatus::toValidator(),
            ],
        ];

        return $rules;
    }

    /**
     * Build the email validation rules.
     *
     * Legacy troopers imported without a real email address were assigned a placeholder
     * value that never passes email-format validation. Skip the format check when the
     * submitted email is unchanged from the trooper's current value, so admins can still
     * edit other profile fields without being forced to fix a legacy placeholder first.
     */
    private function emailRules(?Trooper $trooper): array
    {
        $rules = ['required', 'string', 'max:256'];

        $email_unchanged = $trooper !== null && $this->input(Trooper::EMAIL) === $trooper->email;

        if (!$email_unchanged)
        {
            $rules[] = 'email';
        }

        $rules[] = Rule::unique(Trooper::class, Trooper::EMAIL)
            ->ignore($trooper?->id, Trooper::ID);

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
