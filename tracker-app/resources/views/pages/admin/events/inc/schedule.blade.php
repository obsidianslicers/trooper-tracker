<x-accordion-card :label="'Schedule'">
    @if(!$event->exists)
        <x-message>
            Once saved, shifts can be added/edited for this event.
        </x-message>
    @endif

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Starts:</x-label>
                <x-input-datetime :property="'event_start'"
                                  :value="$event->event_start"
                                  x-model="form.event_start" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Ends:</x-label>
                <x-input-datetime :property="'event_end'"
                                  :value="$event->event_end"
                                  x-model="form.event_end" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <x-label>Website:</x-label>
        <x-input-text :property="'event_website'"
                      :value="$event->event_website"
                      x-model="form.event_website" />
    </x-input-container>
</x-accordion-card>