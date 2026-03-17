<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Provides HTMX-aware validation handling for form requests.
 *
 * This trait modifies the default Laravel validation behavior to work seamlessly with HTMX requests.
 * When validation fails on an HTMX request, it suppresses the automatic redirect that would normally
 * occur, allowing HTMX to handle the response appropriately (typically by updating the DOM with
 * validation errors).
 *
 * Usage:
 * ```php
 * class MyRequest extends FormRequest
 * {
 *     use HtmxValidation;
 *
 *     public function rules(): array
 *     {
 *         return ['field' => 'required'];
 *     }
 * }
 * ```
 */
trait HtmxValidation
{
    /**
     * Handle a failed validation attempt for HTMX requests.
     *
     * Overrides the default Laravel behavior to prevent automatic redirects when validation
     * fails on HTMX requests. For HTMX requests (identified by the 'HX-Request' header),
     * this method returns early without throwing an exception. For standard requests,
     * it delegates to the parent implementation which throws a ValidationException.
     *
     * @param  ValidatorInterface  $validator  The validator instance containing validation errors
     */
    protected function failedValidation(ValidatorInterface $validator): void
    {
        //  avoids failing in HTMX - which causes a redirect in laravel normal form requests
        if ($this->headers->has('HX-Request'))
        {
            return;
        }

        parent::failedValidation($validator);
    }

    /**
     * Manually validate the request data and return validated input.
     *
     * Creates a validator instance using the request data and rules, then validates the input.
     * If validation fails, throws a ValidationException with the validation errors. This method
     * is useful when you need explicit control over when validation occurs, rather than relying
     * on Laravel's automatic validation in the controller.
     *
     * @return array<string, mixed> The validated data that passed all validation rules
     *
     * @throws \Illuminate\Validation\ValidationException When validation fails
     */
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
