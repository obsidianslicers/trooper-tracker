<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Rules\Admin\Troopers\PendingTrooperRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for denying a trooper request.
 *
 * This class defines validation rules for denying a trooper request.
 *
 * Administrators and moderators can deny trooper requests.
 */
class DenyTrooperRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * Verifies that the trooper request exists in the route and that the authenticated
     * user is an administrator or moderator.
     *
     * @return bool Returns true if the user is an administrator or moderator
     *
     * @throws AuthorizationException If the trooper is not found in the route
     */
    public function authorize(): bool
    {
        $trooper_request = $this->route('trooper_request');

        if ($trooper_request === null)
        {
            throw new AuthorizationException('Trooper request not found or unauthorized.');
        }

        return $this->user()->can('moderate', $trooper_request);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Generates dynamic validation rules for denying trooper requests.
     *
     * @return array<string, mixed> The validation rules for denying trooper requests
     */
    public function rules(): array
    {
        return [
            'denial_reason' => [
                'nullable',
                'string',
            ],
            'trooper_request' => [
                new PendingTrooperRequest,
            ],
        ];
    }

    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'trooper_request' => $this->route('trooper_request'),
        ]);
    }
}
