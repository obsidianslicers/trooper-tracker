<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use App\Models\FaqSection;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');

        return $this->user()->can('update', $section);
    }

    public function rules(): array
    {
        return [
            FaqSection::LABEL => [
                'required',
                'string'
            ],
            FaqSection::ICON => [
                'required',
                'string',
                'max:64'
            ],
        ];
    }
}
