<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\HasNormalizers;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for the update notice as read form.
 *
 * This class defines validation rules for marking a notice as read for a trooper.
 * It ensures that the request is properly validated before updating the read status of a notice.
 */
class UpdateNoticeAsReadRequest extends FormRequest
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
        $rules = [];

        return $rules;
    }
}
