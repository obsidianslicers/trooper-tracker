<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Costumes;

use App\Models\Costume;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating an existing costume.
 *
 * This class defines validation rules for updating costume information.
 * It ensures the costume name remains unique among sibling costumes
 * (children of the same parent) while excluding the costume being updated
 * from the uniqueness check.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * Verifies that the costume exists in the route and that the authenticated
     * user has permission to update it.
     *
     * @return bool Returns true if the user can update the costume
     *
     * @throws AuthorizationException if the costume is not found in the route
     */
    public function authorize(): bool
    {
        $costume = $this->route('costume');

        if ($costume === null)
        {
            throw new AuthorizationException('Costume not found or unauthorized.');
        }

        return $this->user()->can('update', $costume);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Validates the costume name ensuring it's unique among sibling costumes,
     * excluding the current costume from the uniqueness check.
     *
     * @return array<string, mixed> The validation rules for updating a costume
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique(Costume::class, Costume::NAME)
                    ->ignore($this->route('costume')),
            ],
        ];

        return $rules;
    }
}
