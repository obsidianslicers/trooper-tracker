<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Http\Requests\HtmxValidation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation for sharing event rosters with coordinators.
 *
 * This request validates the coordinator email when sharing an event's
 * roster. The user must have permission to update the event.
 */
class ShareRosterHtmxRequest extends FormRequest
{
    use HtmxValidation;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Checks if the user has permission to update the event.
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
     * Get the validation rules that apply to the request.
     *
     * Validates that a recipient_email is provided in valid email format.
     *
     * @return array<string, array<int, string>> The validation rules for the request
     */
    public function rules(): array
    {
        return [
            'recipient_email' => [
                'required',
                'string',
                'email',
            ],
        ];
    }
}
