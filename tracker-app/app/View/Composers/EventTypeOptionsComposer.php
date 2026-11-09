<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\EventType;
use Illuminate\View\View;

/**
 * Event types for the event form: the ones offered, in the administrators' order,
 * plus the event's own type if it has since been hidden.
 */
class EventTypeOptionsComposer
{
    public function compose(View $view): void
    {
        $current = $view->getData()['event']->type ?? null;

        $event_types = EventType::query()
            ->where(fn ($q) => $q->where(EventType::IS_ACTIVE, true)
                ->when($current !== null, fn ($q) => $q->orWhere(EventType::SLUG, $current)))
            ->ordered()
            ->get();

        $view->with([
            'event_type_options' => $event_types->pluck(EventType::NAME, EventType::SLUG)->all(),
            'event_type_help' => $event_types
                ->mapWithKeys(fn (EventType $type) => [$type->slug => [
                    'description' => (string) $type->description,
                    'is_counted' => $type->is_counted,
                ]])
                ->all(),
        ]);
    }
}
