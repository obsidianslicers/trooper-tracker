<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Http\Requests\HtmxValidation;
use App\Models\EventGuest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an event guest's setup (status and costume).
 *
 * This request validates changes to a guest's event participation, including
 * their attendance status and costume selection. The costume selection is restricted
 * to costumes from organizations that are allowed to attend the event.
 */
class GuestUpdateHtmxRequest extends FormRequest
{
    use HtmxValidation;

    /**
     * Determine if the user is authorized to make this request
     *
     * Checks if the user is either the guest themselves or a moderator
     * of the guest's organization(s).
     *
     * @throws AuthorizationException if the event guest is not found
     */
    public function authorize(): bool
    {
        $event_guest = $this->route('event_guest');

        if ($event_guest === null)
        {
            throw new AuthorizationException('EventGuest not found or unauthorized.');
        }

        $event = $event_guest->event_shift->event;

        if ($event->status === EventStatus::MANUAL_SELECTION)
        {
            return $this->user()->can('update', $event);
        }

        if ($event_guest->canUpdateName($this->user()))
        {
            return true;
        }

        if ($event_guest->canUpdateStatus($this->user()))
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
            EventGuest::NAME => [
                'nullable',
                'string',
                'max:128',
            ],
            EventGuest::STATUS => [
                'nullable',
                'string',
                'max:16',
                'in:'.EventGuestStatus::toValidator(),
            ],
        ];
    }
}
