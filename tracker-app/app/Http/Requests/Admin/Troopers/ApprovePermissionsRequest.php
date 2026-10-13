<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\PermissionRequest;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an administrator approving a pending permission request.
 */
class ApprovePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_administrator;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permission_request' => [
                function (string $attribute, mixed $value, Closure $fail): void
                {
                    $this->validatePermissionRequest($value, $fail);
                },
            ],
        ];
    }

    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'permission_request' => $this->route('permission_request'),
        ]);
    }

    private function validatePermissionRequest(mixed $value, Closure $fail): void
    {
        if (!$value instanceof PermissionRequest || !$value->isPending())
        {
            $fail('This permission request has already been reviewed.');

            return;
        }

        if ($value->membership_role !== null
            && $value->trooper->membership_role === MembershipRole::ADMINISTRATOR)
        {
            $fail('This trooper is now an administrator. Deny the request instead.');
        }
    }
}
