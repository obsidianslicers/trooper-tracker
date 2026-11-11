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
            'shifts.*.charity_name' => ['nullable', 'string', 'max:128'],
            'shifts.*.charity_hours' => ['nullable', 'integer', 'min:0'],
            'shifts.*.charity_direct_funds' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'shifts.*.charity_indirect_funds' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'shifts.*.charity_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'shifts.*.charity_direct_funds.*' => 'Enter an amount in dollars and cents, like 12.50.',
            'shifts.*.charity_indirect_funds.*' => 'Enter an amount in dollars and cents, like 12.50.',
        ];
    }

    /**
     * Accept amounts typed the way they're shown ("$1,234.50").
     */
    protected function prepareForValidation(): void
    {
        $shifts = $this->input('shifts');

        if (!is_array($shifts))
        {
            return;
        }

        foreach ($shifts as $id => $input)
        {
            foreach (['charity_direct_funds', 'charity_indirect_funds'] as $field)
            {
                if (is_array($input) && is_string($input[$field] ?? null))
                {
                    $shifts[$id][$field] = str_replace(['$', ',', ' '], '', $input[$field]);
                }
            }
        }

        $this->merge(['shifts' => $shifts]);
    }
}
