<x-input-container>
    <x-label>Name:</x-label>
    <x-input-text :property="'name'"
                  :value="$event->name"
                  x-model.fill="form.name" />
</x-input-container>

<x-input-container>
    {{-- x-data sits on a plain element: Blade doesn't compile @js inside a component tag's attributes. --}}
    <div x-data="{ event_type: @js((string) old('event_type_id', $event->event_type_id)), help: @js($event_type_help) }">
        <x-label for="event_type_id">Event Type:</x-label>
        <x-input-select :property="'event_type_id'"
                        :options="$event_type_options"
                        :value="$event->event_type_id"
                        x-model="event_type" />
        <p class="form-help"
           x-show="help[event_type]"
           x-cloak>
            <i class="text-muted"
               x-text="help[event_type]?.description"></i>
            {{-- A div, not .d-block: Bootstrap's !important display would beat x-show. --}}
            <div class="text-warning"
                 x-show="help[event_type] && !help[event_type].is_counted">
                <i class="fa fa-fw fa-circle-info"></i>
                {{ \App\Models\EventType::NOT_COUNTED_NOTICE }}
            </div>
        </p>
    </div>
</x-input-container>

<x-input-container>
    <x-label>Status:</x-label>
    <x-input-select :property="'status'"
                    :options="\App\Enums\EventStatus::toArray()"
                    :value="$event->status->value" />
</x-input-container>
