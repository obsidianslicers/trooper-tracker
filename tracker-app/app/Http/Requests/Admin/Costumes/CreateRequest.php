<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Costumes;

use App\Models\Costume;
use App\Rules\Admin\Costumes\UniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for creating a new costume.
 *
 * This class defines validation rules for creating child costumes under a parent
 * costume. It ensures the costume name is unique among siblings within the
 * same parent costume using a custom validation rule.
 */
class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true if the user has permission to create costumes, false otherwise
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Costume::class);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Validates the costume name ensuring it's unique among sibling costumes
     * under the same parent costume.
     *
     * @return array<string, mixed> The validation rules for creating a costume
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique(Costume::class, Costume::NAME),
            ],
        ];

        return $rules;
    }
}
