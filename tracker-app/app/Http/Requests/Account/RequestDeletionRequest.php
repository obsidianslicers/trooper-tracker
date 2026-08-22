<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Models\Costume;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for the delete costume form.
 *
 * This class defines validation rules for deleting a costume from a trooper's profile.
 */
class RequestDeletionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true as any authenticated user can update their profile
     */
    public function authorize(): bool
    {
        $trooper = $this->user();

        return $this->user()->can('requestDeletion', $trooper);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed> The validation rules for the profile update form
     */
    public function rules(): array
    {
        return [];
    }
}
