<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventGuestStatus;
use App\Enums\EventTrooperStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating the organizations associated with an Event.
 */
class UpdateTroopersRequest extends FormRequest
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
            'roster_complete' => ['required', 'accepted'],
            'troopers.*.status' => ['nullable', EventTrooperStatus::toValidator()],
            'troopers.*.event_shift_station_id' => ['nullable', 'integer', 'exists:tt_event_shift_stations,id'],
            'troopers.*.costume_id' => ['nullable', 'integer', 'exists:tt_costumes,id'],
            'troopers.*.organization_selection' => ['nullable', 'boolean'],
            'troopers.*.organization_ids' => ['nullable', 'array'],
            'troopers.*.organization_ids.*' => ['integer'],
            'guests.*.status' => ['nullable', EventGuestStatus::toValidator()],
        ];

        return $rules;
    }

    /**
     * PHP silently drops input past max_input_vars; the trailing sentinel goes missing first.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roster_complete.*' => 'The roster form was too large to submit in full, so no '
                .'changes were saved. Edit fewer rows and try again.',
        ];
    }
}
