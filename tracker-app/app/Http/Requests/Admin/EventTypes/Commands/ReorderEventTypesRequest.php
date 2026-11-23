<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\EventTypes\Commands;

use App\Models\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderEventTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', EventType::class);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', Rule::exists(EventType::class, EventType::ID)],
        ];
    }
}
