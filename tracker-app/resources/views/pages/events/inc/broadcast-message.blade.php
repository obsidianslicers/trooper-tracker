<div id="broadcast-message">
    @if($can_moderate && $event->is_active)
        <x-transmission-bar :id="'broadcast-message'" />

        <div class="card my-4 mb-4 border-secondary">
            <div class="card-header bg-secondary text-white d-flex align-items-center">
                <b class="mb-0">Broadcast Message</b>
            </div>
            <div class="card-body">
                @isset($message)
                    <x-message>
                        {{ $message }}
                    </x-message>
                @endisset
                <p class="small text-muted">
                    <i class="fa fa-fw fa-triangle-exclamation text-warning"></i>
                    Send an instant notification to all troopers with a "{{ App\Enums\EventTrooperStatus::GOING->value }}"
                    status across all shifts. Messages are delivered through each subscriber's preferred notification
                    methods, such as mobile alerts and email.
                </p>

                <form hx-post="{{ route('events.broadcast-message-htmx', compact('event')) }}"
                      hx-target="#broadcast-message"
                      hx-indicator="#transmission-bar-broadcast-message"
                      hx-swap="outerHTML">
                    @csrf
                    <div class="row g-2">
                        <div class="col-sm-12">
                            @error('message')
                                <x-message :type="'danger'">
                                    {{ $errors->first('message') }}
                                </x-message>
                            @enderror
                            <x-input-text :property="'message'"
                                          :multiline="true" />
                        </div>
                        <div class="col-sm-12">
                            <x-submit-button class="w-100">
                                <i class="fa fa-fw fa-envelope me-1"></i>
                                Send Message
                            </x-submit-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>