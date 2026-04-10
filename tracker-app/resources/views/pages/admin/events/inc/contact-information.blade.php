<x-accordion-card :label="'Contact Information (Commanding Officer)'">
    <x-input-container>
        <x-label>Contact Name:</x-label>
        <x-input-text :property="'contact_name'"
                      :value="$event->contact_name"
                      x-model="form.contact_name" />
    </x-input-container>

    <x-input-container>
        <x-label>Contact Phone:</x-label>
        <x-input-text :property="'contact_phone'"
                      :value="$event->contact_phone"
                      x-model="form.contact_phone" />
    </x-input-container>

    <x-input-container>
        <x-label>Contact Email:</x-label>
        <x-input-text :property="'contact_email'"
                      :value="$event->contact_email"
                      x-model="form.contact_email" />
    </x-input-container>
</x-accordion-card>