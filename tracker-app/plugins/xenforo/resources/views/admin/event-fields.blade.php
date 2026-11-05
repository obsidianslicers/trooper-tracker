@php
    $xenforo_thread = $event->exists
        ? \TroopTracker\Plugins\Xenforo\Models\EventThread::forEvent($event->id)
        : null;
@endphp
@if(TroopTracker\Plugins\Xenforo\Services\XenforoService::isConfigured())
    <x-input-container>
        <x-label>Create XenForo Thread for this Event:</x-label>
        <x-input-yesno :property="'xenforo_create_thread'"
                       :value="$xenforo_thread?->should_create_thread ?? true" />
        <x-input-help>
            Set to "No" to skip creating a forum thread when this event is posted.
        </x-input-help>
    </x-input-container>

    <x-input-container>
        <x-label>Forum Thread ID:</x-label>
        <x-input-text :property="'xenforo_thread_id'"
                      :value="$xenforo_thread?->thread_id" />
    </x-input-container>

    <x-input-container>
        <x-label>Forum Post ID:</x-label>
        <x-input-text :property="'xenforo_post_id'"
                      :value="$xenforo_thread?->post_id" />
    </x-input-container>
@endif
