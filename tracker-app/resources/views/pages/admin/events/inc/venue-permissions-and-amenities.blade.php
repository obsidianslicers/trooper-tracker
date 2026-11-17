<x-accordion-card :label="__('admin.Venue Permissions & Amenities (Logistics & Support)')">
    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>{{ __('admin.Secure Staging Area:') }}</x-label>
                <x-input-yesno :property="'secure_staging_area'"
                               :value="$event->secure_staging_area"
                               x-model.fill="form.secure_staging_area" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>{{ __('admin.Parking Available:') }}</x-label>
                <x-input-yesno :property="'parking_available'"
                               :value="$event->parking_available"
                               x-model.fill="form.parking_available" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>{{ __('admin.Allow Blasters:') }}</x-label>
                <x-input-yesno :property="'allow_blasters'"
                               :value="$event->allow_blasters"
                               x-model.fill="form.allow_blasters" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>{{ __('admin.Allow Props:') }}</x-label>
                <x-input-yesno :property="'allow_props'"
                               :value="$event->allow_props"
                               x-model.fill="form.allow_props" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <x-label>{{ __('admin.Limited Mobility Accessible:') }}</x-label>
        <x-input-yesno :property="'accessible'"
                       :value="$event->accessible"
                       x-model.fill="form.accessible" />
    </x-input-container>

    <x-input-container>
        <x-label>{{ __('admin.Amenities:') }}</x-label>
        <x-input-text :property="'amenities'"
                      :multiline="true"
                      :value="$event->amenities"
                      x-model.fill="form.amenities" />
    </x-input-container>
</x-accordion-card>