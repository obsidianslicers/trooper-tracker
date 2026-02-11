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
@endif
@if($event_shift->is_open && ($event_shift->canSignUpFriend(Auth::user()) || $can_moderate))
    {{-- if they are a normal user and already signed up - they can sign up a friend --}}
    {{-- or they are a moderator - they can sign up a friend --}}
    <button class="btn btn-sm btn-outline-info"
            hx-get="{{ route('pickers.trooper', ['property' => 'add-shift-trooper-' . $event_shift->id, 'event' => 'trooper:selected']) }}"
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
         hx-trigger="trooper:selected[event.detail.property == 'add-shift-trooper-{{ $event_shift->id }}'] from:document"
         hx-select="#shift-container-{{ $event_shift->id }}"
         hx-target="#shift-container-{{ $event_shift->id }}"
         hx-swap="outerHTML"
         hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}"></div>
@endif