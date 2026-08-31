<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use App\Models\FaqSection;
use Illuminate\Foundation\Http\FormRequest;

class CreateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', FaqSection::class);
    }

    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string'
            ],
            'icon' => [
                'required',
                'string',
                'max:64'
            ],
        ];
    }
}
