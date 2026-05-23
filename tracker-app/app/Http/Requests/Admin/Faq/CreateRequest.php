<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use App\Models\FaqSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', Rule::exists(FaqSection::class, FaqSection::ID)],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'url', 'max:512'],
        ];
    }
}
