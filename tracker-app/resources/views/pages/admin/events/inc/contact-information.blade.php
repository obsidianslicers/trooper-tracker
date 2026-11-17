<x-accordion-card :label="__('admin.Contact Information (Point of Contact)')">
    <x-input-container>
        <x-label>{{ __('admin.Contact Name:') }}</x-label>
        <x-input-text :property="'contact_name'"
                      :value="$event->contact_name"
                      x-model.fill="form.contact_name" />
    </x-input-container>

    <x-input-container>
        <x-label>{{ __('admin.Contact Phone:') }}</x-label>
        <x-input-text :property="'contact_phone'"
                      :value="$event->contact_phone"
                      x-model.fill="form.contact_phone" />
    </x-input-container>

    <x-input-container>
        <x-label>{{ __('admin.Contact Email:') }}</x-label>
        <x-input-text :property="'contact_email'"
                      :value="$event->contact_email"
                      x-model.fill="form.contact_email" />
    </x-input-container>
</x-accordion-card>