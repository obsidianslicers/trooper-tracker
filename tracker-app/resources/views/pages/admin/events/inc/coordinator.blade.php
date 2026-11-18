<x-input-container>
    <x-label for="coordinator_trooper_id">{{ __('admin.Event Coordinator:') }}</x-label>
    <x-input-select :property="'coordinator_trooper_id'"
                    :options="$coordinator_options"
                    :value="$event->coordinator_trooper_id"
                    :placeholder="__('admin.:name (created the event)', ['name' => $coordinator_creator])" />
    <x-input-help>
        {{ __("admin.Shown on the event page, and they can edit this event. It's whoever created the event unless you pick an event coordinator or command staff member who is going or attended. If their status changes to anything else, it goes back to the creator. The forum thread stays under the creator's name.") }}
    </x-input-help>
</x-input-container>
