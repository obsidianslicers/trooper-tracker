<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an existing Event.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Checks if the user has permission to update the event specified in the route.
     *
     * @return bool
     * @throws AuthorizationException if the event is not found.
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event == null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed> The validation rules for the request.
     */
    public function rules(): array
    {
        $rules = [
            Event::NAME => ['required', 'string', 'max:128',],
            Event::STARTS_AT => ['required', 'date'],
            Event::ENDS_AT => ['required', 'date', 'after:starts_at'],
            Event::STATUS => ['required', 'string', 'max:16', 'in:' . EventStatus::toValidator()],
            Event::LIMIT_ORGANIZATIONS => ['required', 'boolean'],
            Event::TROOPERS_ALLOWED => ['required_if:' . Event::LIMIT_ORGANIZATIONS . ',true', 'integer', 'between:1,99999'],
            Event::HANDLERS_ALLOWED => ['required_if:' . Event::LIMIT_ORGANIZATIONS . ',true', 'integer', 'between:0,99999'],
        ];

        return $rules;
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Event::TROOPERS_ALLOWED . '.required_if' =>
                'The troopers allowed field is required when limit organizations is set to Yes.',
            Event::HANDLERS_ALLOWED . '.required_if' =>
                'The handlers allowed field is required when limit organizations is set to Yes.',
        ];
    }

}