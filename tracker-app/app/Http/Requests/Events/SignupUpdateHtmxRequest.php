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
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating an event trooper's setup (status and costume).
 *
 * This request validates changes to a trooper's event participation, including
 * their attendance status and costume selection. The costume selection is restricted
 * to costumes from organizations that are allowed to attend the event.
 */
class SignupUpdateHtmxRequest extends FormRequest
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

        if ($event_trooper->canUpdateCostume($this->user()))
        {
            return true;
        }

        if ($event_trooper->canUpdateStatus($this->user()))
        {
            return true;
        }

        if ($event_trooper->canCancel($this->user()))
        {
            return true;
        }

        if ($event_trooper->canReSignUp($this->user()))
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
        $event_trooper = $this->route('event_trooper');
        $event_shift = $event_trooper->event_shift;
        $event = $event_shift->event;

        $new_org_id = $this->input('organization_id') ? (int) $this->input('organization_id') : null;
        $effective_org_id = $new_org_id ?? $event_trooper->organization_id;

        $organization_ids = $effective_org_id !== null
            ? collect([$effective_org_id])
            : $event->event_organizations()->pluckCanAttend($event_shift);

        $valid_costume_ids = Costume::forTrooper($event_trooper->trooper_id, $organization_ids)
            ->pluck('id')
            ->toArray();

        $eligible_org_ids = $event_trooper->trooper->eligibleOrgsForEvent($event)->pluck('id')->toArray();

        return [
            EventTrooper::ORGANIZATION_ID => [
                'nullable',
                'int',
                Rule::in($eligible_org_ids),
            ],
            EventTrooper::STATUS => [
                'nullable',
                'string',
                'max:16',
                'in:' . EventTrooperStatus::toValidator(),
            ],
            EventTrooper::COSTUME_ID => [
                'nullable',
                'int',
                Rule::in($valid_costume_ids),
            ],
            EventTrooper::BACKUP_COSTUME_ID => [
                'nullable',
                'int',
                Rule::in($valid_costume_ids),
            ],
            'resign_up' => ['nullable', 'boolean'],
        ];
    }
}
