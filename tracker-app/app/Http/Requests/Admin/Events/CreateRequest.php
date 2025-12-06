<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for creating a new Event.
 */
class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Uses the EventPolicy to determine if the user can create an event.
        return $this->user()->can('create', Event::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed> The validation rules for the request.
     */
    public function rules(): array
    {
        $rules = [
            Event::NAME => [
                'required',
                'string',
                'max:128',
            ],
            Event::STARTS_AT => ['required', 'date'],
            Event::ENDS_AT => ['required', 'date', 'after:starts_at'],
        ];

        // An event must belong to an organization.
        $rules[Event::ORGANIZATION_ID] = [
            'required',
            Rule::exists(Organization::class, Organization::ID)
        ];

        return $rules;
    }
}