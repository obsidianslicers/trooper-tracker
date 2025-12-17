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
                @if($event_shift->canSignUp(Auth::user()))
                    <button class="btn btn-sm btn-outline-success"
                            hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                            hx-select="#shift-container-{{ $event_shift->id }}"
                            hx-target="#shift-container-{{ $event_shift->id }}"
                            hx-swap="outerHTML"
                            hx-trigger="click"
                            hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}">
                        <i class="fa fa-fw fa-plus-circle me-2"></i>
                        @if(Auth::user()->is_handler)
                            Handler Sign Up
                        @else
                            Trooper Sign Up
                        @endif
                    </button>
                @elseif($event_shift->canSignUpFriend(Auth::user()))
                    <button class="btn btn-sm btn-outline-info"
                            hx-get="{{ route('pickers.trooper', ['property' => 'add-shift-trooper-' . $event_shift->id]) }}"
                            hx-target="#modal-picker .modal-body"
                            hx-trigger="click"
                            data-bs-toggle="modal"
                            data-bs-target="#modal-picker">
                        <i class="fa fa-fw fa-plus-circle me-2"></i>
                        Add a Trooper
                    </button>
                    {{-- TO CATCH THE MODAL TROOPER PICKER PICK --}}
                    <div class="d-none"
                         hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                         hx-vals="js:{trooper_id: event.detail.id}"
                         hx-trigger="trooper:selected from:document"
                         hx-select="#shift-container-{{ $event_shift->id }}"
                         hx-target="#shift-container-{{ $event_shift->id }}"
                         hx-swap="outerHTML"
                         hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}"></div>
                @endif
            </div>
        </div>
    </x-accordion-card>
</div>