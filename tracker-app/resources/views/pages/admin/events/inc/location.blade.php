<x-accordion-card :label="'Location'">
    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Latitude:</x-label>
                <x-input-text :property="'latitude'"
                              :value="$event->latitude" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Longitude:</x-label>
                <x-input-text :property="'longitude'"
                              :value="$event->longitude" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <x-label>Venue:</x-label>
        <x-input-text :property="'venue'"
                      :value="$event->venue" />
    </x-input-container>

    <x-input-container>
        <x-label>Venue Address:</x-label>
        <x-input-text :property="'venue_address'"
                      :value="$event->venue_address" />
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>City:</x-label>
                <x-input-text :property="'venue_city'"
                              :value="$event->venue_city" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>State:</x-label>
                <x-input-text :property="'venue_state'"
                              :value="$event->venue_state" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Zip:</x-label>
                <x-input-text :property="'venue_zip'"
                              :value="$event->venue_zip" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Country:</x-label>
                <x-input-text :property="'venue_country'"
                              :value="$event->venue_country" />
            </div>
        </div>
    </x-input-container>
</x-accordion-card>