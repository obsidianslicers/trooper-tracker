<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HtmxFormRequest extends FormRequest
{
    protected function failedValidation(ValidatorInterface $validator): void
    {
        //  avoids failing in HTMX
    }

    public function validateInputs(): array
    {
        $validator = Validator::make($this->all(), $this->rules());

        if ($validator->fails())
        {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}