<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->can('update', $trooper);
    }

    public function rules(): array
    {
        return [
            Trooper::DATE_OF_BIRTH => ['nullable', 'date'],
            'guardian_email'       => [
                'nullable',
                'email',
                'max:256',
                Rule::exists('tt_troopers', Trooper::EMAIL),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('guardian_email') === '') {
            $this->merge(['guardian_email' => null]);
        }
    }
}
