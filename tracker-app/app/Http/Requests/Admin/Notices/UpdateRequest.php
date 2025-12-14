<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Notices;

use App\Enums\NoticeType;
use App\Models\Notice;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an existing notice.
 *
 * This class defines validation rules for updating notice information, including
 * title, message, type, and date range. The organization assignment is immutable
 * after creation and cannot be modified during an update.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Verifies that the notice exists in the route and that the authenticated
     * user has permission to update it.
     *
     * @return bool Returns true if the user can update the notice.
     * @throws AuthorizationException if the notice is not found in the route.
     */
    public function authorize(): bool
    {
        $notice = $this->route('notice');

        if ($notice == null)
        {
            throw new AuthorizationException('Notice not found or unauthorized.');
        }

        return $this->user()->can('update', $notice);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the notice title, message, type enum value, and date range.
     * The organization is immutable and not included in update validation.
     *
     * @return array<string, mixed> The validation rules for updating a notice.
     */
    public function rules(): array
    {
        $rules = [
            Notice::TITLE => [
                'required',
                'string',
                'max:128',
            ],
            Notice::MESSAGE => [
                'required',
                'string',
            ],
            Notice::TYPE => [
                'required',
                'string',
                'max:16',
                'in:' . NoticeType::toValidator()
            ],
            Notice::STARTS_AT => ['required', 'date'],
            Notice::ENDS_AT => ['required', 'date', 'after:starts_at'],
        ];

        return $rules;
    }
}