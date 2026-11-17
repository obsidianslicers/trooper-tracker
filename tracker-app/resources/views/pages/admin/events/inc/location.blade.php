<x-accordion-card :label="__('admin.Location (Mission Coordinates)')">
    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>{{ __('admin.Latitude:') }}</x-label>
                <x-input-text :property="'latitude'"
                              :value="$event->latitude"
                              x-model.fill="form.latitude" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>{{ __('admin.Longitude:') }}</x-label>
                <x-input-text :property="'longitude'"
                              :value="$event->longitude"
                              x-model.fill="form.longitude" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <x-label>{{ __('admin.Venue:') }}</x-label>
        <x-input-text :property="'venue'"
                      :value="$event->venue"
                      x-model.fill="form.venue" />
    </x-input-container>

    <x-input-container>
        <x-label>{{ __('admin.Venue Address:') }}</x-label>
        <x-input-text :property="'venue_address'"
                      :value="$event->venue_address"
                      x-model.fill="form.venue_address" />
        <x-input-help>
            {{ __('admin.The full address of the venue, including street, city, state, and zip.') }}
        </x-input-help>
    </x-input-container>
</x-accordion-card>