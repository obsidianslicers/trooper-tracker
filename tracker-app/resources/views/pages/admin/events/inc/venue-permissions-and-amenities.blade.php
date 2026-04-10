<x-accordion-card :label="'Venue Permissions & Amenities (Logistics & Support)'">
    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Secure Staging Area:</x-label>
                <x-input-yesno :property="'secure_staging_area'"
                               :value="$event->secure_staging_area"
                               x-model="form.secure_staging_area" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Parking Available:</x-label>
                <x-input-yesno :property="'parking_available'"
                               :value="$event->parking_available"
                               x-model="form.parking_available" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Allow Blasters:</x-label>
                <x-input-yesno :property="'allow_blasters'"
                               :value="$event->allow_blasters"
                               x-model="form.allow_blasters" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Allow Props:</x-label>
                <x-input-yesno :property="'allow_props'"
                               :value="$event->allow_props"
                               x-model="form.allow_props" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <x-label>Limited Mobility Accessible:</x-label>
        <x-input-yesno :property="'accessible'"
                       :value="$event->accessible"
                       x-model="form.accessible" />
    </x-input-container>

    <x-input-container>
        <x-label>Amenities:</x-label>
        <x-input-text :property="'amenities'"
                      :multiline="true"
                      :value="$event->amenities"
                      x-model="form.amenities" />
    </x-input-container>
</x-accordion-card>