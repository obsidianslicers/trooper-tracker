<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\FaqSections;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'      => ['required', 'string'],
            'icon'       => ['required', 'string', 'max:64'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
