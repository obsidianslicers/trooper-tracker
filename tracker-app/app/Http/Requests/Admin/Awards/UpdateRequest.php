<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Awards;

use App\Enums\AwardType;
use App\Models\Award;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for the user registration form.
 *
 * This class defines the base validation rules for user registration and dynamically
 * adds rules based on the organizations a user selects, including custom rules for
 * organization-specific identifiers and unit selections. It also customizes error messages
 * for a better user experience.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true as registration is open to guests.
     */
    public function authorize(): bool
    {
        $award = $this->route('award');

        if ($award == null)
        {
            throw new AuthorizationException('Award not found or unauthorized.');
        }

        return $this->user()->can('update', $award);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed> The combined validation rules for the registration form.
     */
    public function rules(): array
    {
        $rules = [
            Award::NAME => [
                'required',
                'string',
                'max:128',
            ],
        ];

        return $rules;
    }
}