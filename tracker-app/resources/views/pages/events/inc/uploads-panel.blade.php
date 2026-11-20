<div id="event-uploads-panel">
    @if ($event->event_uploads->where('is_administrative', true)->isNotEmpty())
        <div class="mt-5">
            <x-section-title>{{ __('events.Instructional Uploads') }}</x-section-title>
            @include('pages.events.inc.upload-display', ['event' => $event, 'is_administrative' => true])
        </div>
    @endif

    <div class="mt-5">
        <x-section-title>{{ __('events.Mission Review') }}</x-section-title>
        @include('pages.events.inc.upload-display', ['event' => $event, 'is_administrative' => false])
    </div>
</div>
