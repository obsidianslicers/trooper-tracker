<?php

declare(strict_types=1);

namespace App\Http\Requests\Events\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\TroopCorrection;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A trooper asking for credit for a completed event they never signed up for: the event and
 * shift, the costume and clubs (only ones they could have trooped in), and the details that
 * prove they were there.
 */
class RequestTroopCorrectionRequest extends FormRequest
{
    public const DETAILS_MAX = 2000;

    private ?Event $event = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', $this->requestableEvent(...)],
            'event_shift_id' => [
                'nullable',
                'integer',
                Rule::exists(EventShift::class, EventShift::ID)->where(EventShift::EVENT_ID, (int) $this->input('event_id')),
            ],
            'costume_id' => ['nullable', 'integer', $this->ownCostume(...)],
            'organization_ids' => ['nullable', 'array'],
            'organization_ids.*' => ['integer', $this->eligibleClub(...)],
            'details' => ['required', 'string', 'min:10', 'max:'.self::DETAILS_MAX],
            'proof_url' => ['nullable', 'url:http,https', 'max:500'],
        ];
    }

    public function event(): Event
    {
        return $this->event ??= Event::findOrFail((int) $this->validated('event_id'));
    }

    public function eventShift(): ?EventShift
    {
        $id = $this->validated('event_shift_id');

        return $id === null ? null : EventShift::find((int) $id);
    }

    private function requestableEvent(string $attribute, mixed $value, Closure $fail): void
    {
        $event = Event::find((int) $value);

        if ($event === null || $event->status !== EventStatus::CLOSED)
        {
            $fail(__('events.Pick a completed event.'));

            return;
        }

        if (!TroopCorrection::canBeRequestedFor($event, $this->user()))
        {
            $fail(__('events.You already have credit or a request waiting for this event.'));
        }
    }

    private function ownCostume(string $attribute, mixed $value, Closure $fail): void
    {
        $shift = $this->shiftForOptions();

        if ($shift === null || !array_key_exists((int) $value, $shift->costumeOptionsFor($this->user())))
        {
            $fail(__('events.Pick one of your costumes.'));
        }
    }

    private function eligibleClub(string $attribute, mixed $value, Closure $fail): void
    {
        $event_trooper = new EventTrooper([
            EventTrooper::TROOPER_ID => $this->user()->id,
            EventTrooper::COSTUME_ID => $this->input('costume_id') ? (int) $this->input('costume_id') : null,
        ]);
        $event_trooper->setRelation('trooper', $this->user());

        if (!$event_trooper->getEligibleCreditParentOrganizations()->contains('id', (int) $value))
        {
            $fail(__('events.That club cannot get credit for this costume.'));
        }
    }

    private function shiftForOptions(): ?EventShift
    {
        $shift_id = $this->input('event_shift_id');

        return $shift_id
            ? EventShift::where(EventShift::EVENT_ID, (int) $this->input('event_id'))->find((int) $shift_id)
            : EventShift::where(EventShift::EVENT_ID, (int) $this->input('event_id'))
                ->orderBy(EventShift::SHIFT_STARTS_AT)
                ->first();
    }
}
