<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Handles the validation for updating the organizations associated with an Event.
 */
class UpdateShiftsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * Checks if the user has permission to update the event specified in the route.
     *
     * @throws AuthorizationException if the event is not found
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event === null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed> The validation rules for the request
     */
    public function rules(): array
    {
        $rules = [
            'shifts.*.date'                   => ['required', 'date'],
            'shifts.*.starts_at'              => ['required', 'date_format:H:i'],
            'shifts.*.ends_at'                => ['required', 'date_format:H:i'],
            'shifts.*.status'                 => ['nullable', 'in:'.EventStatus::toValidator()],
            'shifts.*.charity_name'           => ['nullable', 'string', 'max:512'],
            'shifts.*.charity_hours'          => ['nullable', 'integer', 'min:0'],
            'shifts.*.charity_direct_funds'   => ['nullable', 'integer', 'min:0'],
            'shifts.*.charity_indirect_funds' => ['nullable', 'integer', 'min:0'],
            'shifts.*.charity_notes'          => ['nullable', 'string'],
        ];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('shifts', []) as $key => $shift)
            {
                if (empty($shift['status']) || $shift['status'] !== EventStatus::CLOSED->value)
                {
                    continue;
                }

                if (empty($shift['date']) || empty($shift['ends_at']))
                {
                    continue;
                }

                $shift_ends_at = Carbon::parse($shift['date'].' '.$shift['ends_at']);

                if ($shift_ends_at->isFuture())
                {
                    $validator->errors()->add(
                        "shifts.{$key}.status",
                        'Cannot close a shift that has not yet ended ('.$shift_ends_at->format('M j, Y g:i A').').',
                    );
                }
            }
        });
    }
}
