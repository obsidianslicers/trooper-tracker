<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;

class MarkUntrustedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        return $trooper instanceof Trooper
            && $this->user()->can('markUntrusted', $trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            Trooper::UNTRUSTED_REASON => ['required', 'string', 'max:512'],
            'review_costumes' => ['nullable', 'boolean'],
        ];
    }
}
