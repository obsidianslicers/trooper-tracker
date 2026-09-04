<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');

        if ($section === null)
        {
            throw new AuthorizationException('FAQ section not found or unauthorized.');
        }

        return $this->user()->can('delete', $section);
    }

    public function rules(): array
    {
        return [
            function (Validator $validator): void
            {
                $section = $this->route('section');

                if ($section->faqs()->exists())
                {
                    $validator->errors()->add(
                        'section',
                        'An FAQ section with FAQs cannot be deleted.'
                    );
                }
            },
        ];
    }
}
