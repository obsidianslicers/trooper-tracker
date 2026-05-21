<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use App\Enums\FaqSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section'     => ['required', 'string', Rule::in(array_column(FaqSection::cases(), 'value'))],
            'title'       => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'video_url'   => ['nullable', 'string', 'url', 'max:512'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
