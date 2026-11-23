<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Did you make it?" answered from the bell, the notifications page or the event page: by the
 * trooper (or whoever added them, or their guardian), or by a moderator of the event.
 */
class ConfirmShiftAttendanceRequest extends FormRequest
{
    public const STATUS = 'status';

    public function authorize(): bool
    {
        /** @var EventTrooper|null $event_trooper */
        $event_trooper = $this->route('event_trooper');

        $user = $this->user();

        if ($event_trooper === null || $user === null)
        {
            return false;
        }

        return $event_trooper->canMarkAttendance(
            $user,
            $user->isModeratorForOrganization($event_trooper->event_shift->event->organization),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            self::STATUS => [
                'required',
                Rule::in([EventTrooperStatus::ATTENDED->value, EventTrooperStatus::UNABLE_TO_ATTEND->value]),
            ],
        ];
    }

    public function answer(): EventTrooperStatus
    {
        return EventTrooperStatus::from($this->validated(self::STATUS));
    }
}
