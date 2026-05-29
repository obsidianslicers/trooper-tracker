<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Requests\HtmxValidation;
use App\Models\Costume;
use App\Models\EventTrooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an event trooper's attendance (status and costume).
 *
 * This request validates changes to a trooper's event participation, including
 * their attendance status and costume selection. The costume selection is restricted
 * to costumes from organizations that are allowed to attend the event.
 */
class AttendanceUpdateHtmxRequest extends FormRequest
{
    use HtmxValidation;

    /**
     * Determine if the user is authorized to make this request
     *
     * Checks if the user is either the trooper themselves or a moderator
     * of the trooper's organization(s).
     *
     * @throws AuthorizationException if the event trooper is not found
     */
    public function authorize(): bool
    {
        $event_trooper = $this->route('event_trooper');

        if ($event_trooper === null)
        {
            throw new AuthorizationException('EventTrooper not found or unauthorized.');
        }

        $event = $event_trooper->event_shift->event;

        if ($event->status === EventStatus::MANUAL_SELECTION)
        {
            return $this->user()->can('update', $event);
        }

        if ($event_trooper->canMarkAttendance($event_trooper->event_shift, $this->user()))
        {
            return true;
        }

        if ($this->user()->can('update', $event))
        {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * The costume_id validation is dynamically restricted to only costumes
     * from organizations that can attend this specific event.
     *
     * @return array<string, mixed> The validation rules for the request
     */
    public function rules(): array
    {
        return [
            EventTrooper::STATUS => [
                'nullable',
                'string',
                'max:16',
                'in:'.implode(',', [
                    EventTrooperStatus::ATTENDED->value,
                    EventTrooperStatus::UNABLE_TO_ATTEND->value,
                    EventTrooperStatus::NO_SHOW->value,
                ]),
            ],
        ];
    }
}
