<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A trooper answering "did you make it?" for one of their own troops, from the bell or the
 * notifications page.
 */
class ConfirmShiftAttendanceRequest extends FormRequest
{
    public const STATUS = 'status';

    public function authorize(): bool
    {
        /** @var EventTrooper|null $event_trooper */
        $event_trooper = $this->route('event_trooper');

        return $event_trooper !== null && $event_trooper->trooper_id === $this->user()?->id;
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
