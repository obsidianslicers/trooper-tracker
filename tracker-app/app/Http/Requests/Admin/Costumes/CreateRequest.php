<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Costumes;

use App\Models\Costume;
use App\Rules\Admin\Costumes\UniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request class for validating new costume creation.
 *
 * Defines validation rules for costume creation, ensuring the costume name
 * is unique globally across the system.
 */
class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user has permission to create costumes.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Costume::class);
    }

    /**
     * Get the validation rules that apply to the request.
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
