<x-accordion-card :label="'Location (Mission Coordinates)'">
    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Latitude:</x-label>
                <x-input-text :property="'latitude'"
                              :value="$event->latitude"
                              x-model="form.latitude" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Longitude:</x-label>
                <x-input-text :property="'longitude'"
                              :value="$event->longitude"
                              x-model="form.longitude" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <x-label>Venue:</x-label>
        <x-input-text :property="'venue'"
                      :value="$event->venue"
                      x-model="form.venue" />
    </x-input-container>

    <x-input-container>
        <x-label>Venue Address:</x-label>
        <x-input-text :property="'venue_address'"
                      :value="$event->venue_address"
                      x-model="form.venue_address" />
        <x-input-help>
            The full address of the venue, including street, city, state, and zip.
        </x-input-help>
    </x-input-container>
</x-accordion-card>