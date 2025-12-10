<?php

namespace App\Http\Requests\Auth;

use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return boolean
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            Trooper::USERNAME => [
                'required',
                Rule::exists(Trooper::class, Trooper::USERNAME),
            ],
            Trooper::PASSWORD => ['required'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Trooper::USERNAME . '.required' => 'Username is required.',
            Trooper::USERNAME . '.exists' => 'This username does not exist in our records - do you need to setup your account?',
            Trooper::PASSWORD . '.required' => 'Password is required.',
        ];
    }

    /**
     * Prepare the data for validation by normalizing inputs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'remember_me' => $this->input('remember_me') === 'Y',
        ]);
    }
}
