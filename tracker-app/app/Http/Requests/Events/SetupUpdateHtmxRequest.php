<?php

namespace App\Http\Requests\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Requests\HtmxFormRequest;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;

class SetupUpdateHtmxRequest extends HtmxFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return boolean
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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

    /**
     * Prepare the data for validation by normalizing inputs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'remember_me' => $this->input('remember_me') === 'Y',
        ]);
    }
}
