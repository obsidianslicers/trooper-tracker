<div id="shift-container-{{ $event_shift->id }}">
    <span id="shift-{{ $event_shift->id }}"></span>
    @php($label = $event_shift->short_time_display)
    <x-accordion-card :label="$label"
                      :open="true">
        <x-transmission-bar :id="'shift-' . $event_shift->id" />
        @include('pages.events.inc.shift-header', compact('event_shift'))
        @foreach($event_shift->event_troopers as $event_trooper)
            @include('pages.events.inc.trooper', compact('event_trooper'))
        @endforeach
        <div class="row my-3">
            <div class="col-12 text-end">
                @include('pages.events.inc.shift-add-trooper', compact('event_shift', 'event'))
            </div>
        </div>
    </x-accordion-card>
</div>