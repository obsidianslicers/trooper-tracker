<?php

declare(strict_types=1);

namespace App\Http\Requests\Events\Commands;

use Illuminate\Contracts\Validation\Validator as ValidatorInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Leaves validation to the controller, which calls validateInputs() and chooses the answer: the
 * event page's pickers save one at a time, so a bad value comes back as a toast, not field errors.
 */
trait ValidatesInController
{
    protected function failedValidation(ValidatorInterface $validator): void
    {
        //  the controller validates again with validateInputs() and answers
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateInputs(): array
    {
        $validator = Validator::make($this->all(), $this->rules(), $this->messages(), $this->attributes());

        if ($validator->fails())
        {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
