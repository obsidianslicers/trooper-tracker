<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use App\Models\FaqSection;
use App\Models\Faq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Faq::class);
    }

    public function rules(): array
    {
        return [
            'section_id' => [
                'required',
                'integer',
                Rule::exists(FaqSection::class, FaqSection::ID)
            ],
            'title' => [
                'required',
                'string'
            ],
            'description' => [
                'nullable',
                'string'
            ],
            'video_url' => [
                'nullable',
                'string',
                'url',
                'max:512'
            ],
        ];
    }
}
