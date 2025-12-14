<?php

namespace App\Http\Requests\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Requests\HtmxFormRequest;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating an event trooper's setup (status and costume).
 *
 * This request validates changes to a trooper's event participation, including
 * their attendance status and costume selection. The costume selection is restricted
 * to costumes from organizations that are allowed to attend the event.
 */
class SetupUpdateHtmxRequest extends HtmxFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Checks if the user is either the trooper themselves or a moderator
     * of the trooper's organization(s).
     *
     * @return bool
     * @throws AuthorizationException if the event trooper is not found.
     */
    public function authorize(): bool
    {
        $event_trooper = $this->route('event_trooper');

        if ($event_trooper == null)
        {
            throw new AuthorizationException('EventTrooper not found or unauthorized.');
        }

        if ($this->user()->id == $event_trooper->trooper_id)
        {
            return true;
        }

        return $this->user()->can('moderate', $event_trooper->trooper);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The costume_id validation is dynamically restricted to only costumes
     * from organizations that can attend this specific event.
     *
     * @return array<string, mixed> The validation rules for the request.
     */
    public function rules(): array
    {
        $event_trooper = $this->route('event_trooper');
        $event = $event_trooper->event_shift->event;

        $valid_costume_ids = OrganizationCostume::forEvent($event, $this->user())
            ->pluck('id')
            ->toArray();

        return [
            EventTrooper::STATUS => [
                'nullable',
                'string',
                'max:16',
                'in:' . EventTrooperStatus::toValidator()
            ],
            EventTrooper::COSTUME_ID => [
                'nullable',
                'int',
                Rule::in($valid_costume_ids),
            ],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Trooper::USERNAME . '.required' => 'Username is required.',
            Trooper::USERNAME . '.exists' => 'This username does not exist in our records - do you need to setup your account?',
            Trooper::PASSWORD . '.required' => 'Password is required.',
        ];
    }
}
