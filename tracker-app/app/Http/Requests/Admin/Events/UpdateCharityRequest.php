<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCharityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event === null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    public function rules(): array
    {
        return [
            'shifts.*.charity_name' => ['nullable', 'string', 'max:512'],
            'shifts.*.charity_hours' => ['nullable', 'integer', 'min:0'],
            'shifts.*.charity_direct_funds' => ['nullable', 'integer', 'min:0'],
            'shifts.*.charity_indirect_funds' => ['nullable', 'integer', 'min:0'],
            'shifts.*.charity_notes' => ['nullable', 'string'],
        ];
    }
}
