<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating the organizations associated with an Event.
 */
class UpdateShiftsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Checks if the user has permission to update the event specified in the route.
     *
     * @return bool
     * @throws AuthorizationException if the event is not found.
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event == null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed> The validation rules for the request.
     */
    public function rules(): array
    {
        $rules = [
            'shifts.*.date' => ['required', 'date'],
            'shifts.*.starts_at' => ['required', 'date_format:H:i'],
            'shifts.*.ends_at' => ['required', 'date_format:H:i'],
            'shifts.*.status' => ['nullable', 'in:' . EventStatus::toValidator()],
        ];

        return $rules;
    }
}