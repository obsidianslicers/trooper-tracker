<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth\Commands;

use App\Models\Trooper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginTrooperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            Trooper::EMAIL => [
                'required',
                Rule::exists(Trooper::class, Trooper::EMAIL),
            ],
            Trooper::PASSWORD => ['required'],
            'remember_me' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Trooper::EMAIL.'.required' => __('auth.Email is required.'),
            Trooper::EMAIL.'.exists' => __('auth.This email does not exist in our records - do you need to setup your account?'),
            Trooper::PASSWORD.'.required' => __('auth.Password is required.'),
        ];
    }

    /**
     * Prepare the data for validation by normalizing inputs
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'remember_me' => $this->boolean('remember_me') || $this->input('remember_me') === 'Y',
        ]);
    }
}
