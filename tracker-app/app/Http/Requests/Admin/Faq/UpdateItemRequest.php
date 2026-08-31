<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use App\Models\FaqSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $faq = $this->route('faq');

        return $this->user()->can('update', $faq);
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
