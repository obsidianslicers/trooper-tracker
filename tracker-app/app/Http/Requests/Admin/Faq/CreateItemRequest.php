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
            Faq::SECTION_ID => [
                'required',
                'integer',
                Rule::exists(FaqSection::class, FaqSection::ID)
            ],
            Faq::TITLE => [
                'required',
                'string'
            ],
            Faq::DESCRIPTION => [
                'nullable',
                'string',
                'required_without:' . Faq::VIDEO_URL,
            ],
            Faq::VIDEO_URL => [
                'nullable',
                'string',
                'url',
                'max:512',
                'required_without:' . Faq::DESCRIPTION,
            ],
        ];
    }
}
