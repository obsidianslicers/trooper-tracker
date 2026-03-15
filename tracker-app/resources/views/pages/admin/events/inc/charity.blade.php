<x-accordion-card :label="'Charity'">

    <x-input-container>
        <x-label>Name:</x-label>
        <x-input-text :property="'charity_name'"
                      :value="$event->charity_name" />
    </x-input-container>

    <x-input-container>
        <x-label>Hours:</x-label>
        <x-input-text :property="'charity_hours'"
                      :value="$event->charity_hours" />
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Direct Funds:</x-label>
                <x-input-text :property="'direct_funds'"
                              :value="$event->direct_funds" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Indirect Funds:</x-label>
                <x-input-text :property="'indirect_funds'"
                              :value="$event->indirect_funds" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <x-label>Notes:</x-label>
        <x-input-text :property="'charity_notes'"
                      :multiline="true"
                      :value="$event->charity_notes"
                      x-model="form.charity_notes" />
    </x-input-container>
</x-accordion-card>