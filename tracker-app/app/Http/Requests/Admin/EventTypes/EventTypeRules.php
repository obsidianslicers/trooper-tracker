<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\EventTypes;

use App\Models\EventType;
use Illuminate\Validation\Rule;

/**
 * Rules shared by the create and update event type forms.
 */
trait EventTypeRules
{
    /**
     * @return array<string, mixed>
     */
    protected function eventTypeRules(?EventType $event_type = null): array
    {
        return [
            EventType::NAME => [
                'required',
                'string',
                'max:64',
                Rule::unique(EventType::class, EventType::NAME)->ignore($event_type?->id),
            ],
            EventType::DESCRIPTION => ['nullable', 'string', 'max:1000'],
            EventType::IS_COUNTED => ['required', 'boolean'],
            EventType::IS_ACTIVE => ['required', 'boolean'],
        ];
    }
}
