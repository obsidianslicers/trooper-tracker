{{-- The Share roster form, inside the modal the roster's Share roster button opens
     (pages/events/inc/shifts.blade.php); ShareRosterHtmxController swaps it in place. --}}
<div id="share-roster">
    @if($can_moderate && $event->is_active)
        <x-transmission-bar :id="'share-roster'" />

        @isset($message)
            <x-message>
                {{ $message }}
            </x-message>
        @endisset
        <p class="text-muted">
            {{ __('events.Email the event\'s coordinator a read-only link to every shift\'s roster. The link stops working after the event.') }}
        </p>

        <form hx-post="{{ route('events.share-roster-htmx', compact('event')) }}"
              hx-target="#share-roster"
              hx-indicator="#transmission-bar-share-roster"
              hx-swap="outerHTML">
            @csrf
            @error('recipient_email')
                <x-message :type="'danger'">
                    {{ $message }}
                </x-message>
            @enderror
            <label class="form-label small text-muted mb-1"
                   for="recipient_email">{{ __('events.Coordinator\'s email') }}</label>
            <x-input-text :property="'recipient_email'"
                          placeholder="coordinator@event.com" />
            <div class="d-flex justify-content-end gap-3 align-items-center mt-3">
                <button type="button"
                        class="btn btn-link text-muted"
                        data-bs-dismiss="modal">{{ __('events.Never mind') }}</button>
                <x-submit-button>
                    {{ __('events.Send Roster Link') }}
                </x-submit-button>
            </div>
        </form>
    @endif
</div>
