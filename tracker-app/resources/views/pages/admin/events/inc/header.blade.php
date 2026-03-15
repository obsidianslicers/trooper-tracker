<x-input-container>
    <x-label>Name:</x-label>
    <x-input-text :property="'name'"
                  :value="$event->name"
                  x-model="form.name" />
</x-input-container>

<x-input-container>
    <x-label>Event Type:</x-label>
    <x-input-select :property="'type'"
                    :options="\App\Enums\EventType::toArray()"
                    :value="$event->type->value" />
</x-input-container>

<x-input-container>
    <x-label>Status:</x-label>
    <x-input-select :property="'status'"
                    :options="\App\Enums\EventStatus::toArray()"
                    :value="$event->status->value" />
</x-input-container>