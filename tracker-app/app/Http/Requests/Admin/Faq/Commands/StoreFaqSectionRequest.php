<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq\Commands;

use App\Models\FaqSection;
use Illuminate\Foundation\Http\FormRequest;

class StoreFaqSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', FaqSection::class);
    }

    public function rules(): array
    {
        return [
            FaqSection::LABEL => [
                'required',
                'string',
            ],
            FaqSection::ICON => [
                'required',
                'string',
                'max:64',
            ],
        ];
    }
}
