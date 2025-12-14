<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating a trooper's profile by an administrator.
 *
 * This class defines validation rules for administrators updating trooper profiles,
 * including name, email, phone, and membership status. Administrators can modify
 * any trooper's profile information, including approval status changes.
 */
class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Verifies that the trooper exists in the route and that the authenticated
     * user has permission to update the trooper's profile.
     *
     * @return bool Returns true if the user can update the trooper.
     * @throws AuthorizationException if the trooper is not found in the route.
     */
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper == null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->can('update', $trooper);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the trooper's name, email, phone, and membership status.
     * The membership status can be updated by administrators to approve or manage
     * trooper accounts.
     *
     * @return array<string, mixed> The validation rules for updating a trooper's profile.
     */
    public function rules(): array
    {
        $rules = [
            Trooper::NAME => ['required', 'string', 'max:256'],
            Trooper::EMAIL => ['required', 'string', 'email', 'max:256'],
            Trooper::PHONE => ['nullable', 'string', 'max:16'],
            Trooper::MEMBERSHIP_STATUS => ['nullable', 'string', 'max:16', 'in:' . MembershipStatus::toValidator()],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * This method sanitizes the phone number by removing any non-digit characters.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone'))
        {
            $this->merge([
                'phone' => preg_replace('/\D+/', '', $this->input('phone') ?? ''),
            ]);
        }
    }
}