<x-accordion-card :label="'Location'">
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
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>City:</x-label>
                <x-input-text :property="'venue_city'"
                              :value="$event->venue_city"
                              x-model="form.venue_city" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>State:</x-label>
                <x-input-text :property="'venue_state'"
                              :value="$event->venue_state"
                              x-model="form.venue_state" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Zip:</x-label>
                <x-input-text :property="'venue_zip'"
                              :value="$event->venue_zip"
                              x-model="form.venue_zip" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Country:</x-label>
                <x-input-text :property="'venue_country'"
                              :value="$event->venue_country"
                              x-model="form.venue_country" />
            </div>
        </div>
    </x-input-container>
</x-accordion-card>