<div id="share-roster">
    @if($can_moderate && $event->is_active)
        <x-transmission-bar :id="'share-roster'" />

        <div class="card my-4 mb-4 border-secondary">
            <div class="card-header bg-secondary text-white d-flex align-items-center">
                <b class="mb-0">Share Mission Roster</b>
            </div>
            <div class="card-body">
                @isset($message)
                    <x-message>
                        {{ $message }}
                    </x-message>
                @endisset
                <p class="small text-muted">
                    <i class="fa fa-fw fa-circle-info text-primary"></i>
                    To provide event coordinators with a <strong>read-only</strong> view of the current troop deployment
                    across all shifts, enter the recipient's email address below. The recipient will receive a unique
                    secure link that expires after the mission concludes.
                </p>

                <form hx-post="{{ route('events.share-roster-htmx', compact('event')) }}"
                      hx-target="#share-roster"
                      hx-indicator="#transmission-bar-share-roster"
                      hx-swap="outerHTML">
                    @csrf
                    <div class="row g-2">
                        <div class="col-sm-12 col-md-8">
                            @error('recipient_email')
                                <x-message :type="'danger'">
                                    {{ $message }}
                                </x-message>
                            @enderror
                            <x-input-text :property="'recipient_email'"
                                          placeholder="coordinator@event.com" />
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <x-submit-button class="w-100">
                                <i class="fa fa-fw fa-envelope me-1"></i>
                                Send Roster Link
                            </x-submit-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>