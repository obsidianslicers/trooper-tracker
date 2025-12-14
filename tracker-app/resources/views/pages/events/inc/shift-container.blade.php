<div id="shift-container-{{ $event_shift->id }}">
    @php($label = $event_shift->short_time_display)
    <x-accordion-card :label="$label"
                      :open="true">
        <x-transmission-bar :id="'shift-' . $event_shift->id" />
        @include('pages.events.inc.shift', compact('event_shift'))
        @foreach($event_shift->event_troopers as $event_trooper)
            @include('pages.events.inc.troopers', compact('event_trooper'))
        @endforeach
        @if($event_shift->canSignUp(Auth::user()))
            <div class="row my-3">
                <div class="col-12 text-end">
                    <button class="btn btn-outline-success"
                            hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                            hx-select="#shift-container-{{ $event_shift->id }}"
                            hx-target="#shift-container-{{ $event_shift->id }}"
                            hx-swap="outerHTML"
                            hx-trigger="click"
                            hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}">
                        <i class="fa fa-fw fa-plus-circle me-2"></i>
                        @if(Auth::user()->isHandler())
                            Handler Sign Up
                        @else
                            Trooper Sign Up
                        @endif
                    </button>
                </div>
            </div>
        @endif
    </x-accordion-card>
</div>