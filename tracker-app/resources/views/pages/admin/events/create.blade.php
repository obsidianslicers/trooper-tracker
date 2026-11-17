@extends('layouts.base')

@section('page-title', __('admin.Create Event'))

@section('content')

    <x-transmission-bar :id="'event'" />

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate"
                  x-data="Admin.Events.eventCreation({ mode: '{{ $mode }}', organizationId: {{ $event->organization_id ?? 'null' }}, organizationName: '{{ $event->organization?->name ?? 'null' }}', clubName: '{{ $event->organization?->getPrimaryClub()?->name ?? 'null' }}' })">
                @csrf

                <x-message>
                    {!! __('admin.By default when creating a new event, ALL clubs are defaulted to attend unless otherwise updated. See <b>Character Requests & Attendee Limits</b> section to make adjustments to which clubs can attend.') !!}
                    <br />
                    <br />
                    {{ __('admin.All Events start is a :draft status, which means they are not visible for sign up until the status is changed to :open or :locked.', [
                        'draft' => \App\Enums\EventStatus::DRAFT->label(),
                        'open' => \App\Enums\EventStatus::OPEN->label(),
                        'locked' => \App\Enums\EventStatus::SIGN_UP_LOCKED->label(),
                    ]) }}
                </x-message>

                <x-input-container x-ref="organizationPicker">
                    <x-label>
                        {{ __('admin.Hosting Organization:') }}
                    </x-label>
                    <x-input-picker :property="'organization_id'"
                                    :route="'pickers.organization'"
                                    :params="['event_host_only' => true]"
                                    :text="$event->organization->name ?? 'Select a Host'"
                                    :value="$event->organization_id" />
                </x-input-container>

                <div x-show="form.organization_id > 0">
                    <x-input-container>
                        <div class="btn-group mb-3"
                             role="group">
                            <input type="radio"
                                   class="btn-check"
                                   name="mode"
                                   id="mode-email"
                                   autocomplete="off"
                                   @checked($mode === 'email')
                                   x-model="mode"
                                   value="email" />
                            <label class="btn btn-outline-primary"
                                   for="mode-email">
                                {{ __('admin.Parse Message') }}
                            </label>
                            <input type="radio"
                                   class="btn-check"
                                   name="mode"
                                   id="mode-manual"
                                   autocomplete="off"
                                   @checked($mode === 'manual')
                                   x-model="mode"
                                   value="manual" />
                            <label class="btn btn-outline-primary"
                                   for="mode-manual">
                                {{ __('admin.Input Manually') }}
                            </label>
                        </div>
                    </x-input-container>

                    <x-input-container x-show="mode == 'email'">
                        <x-label>
                            <div x-show="form.organization_id > 0">
                                <b x-text="form.club_name"></b>
                                {{ __('admin.Message:') }}
                            </div>
                            <div x-show="form.organization_id == 0">
                                {{ __('admin.Message to Parse:') }}
                            </div>
                        </x-label>
                        <x-input-text :property="'source'"
                                      :multiline="true"
                                      :placeholder="__('admin.Event Name: Name of Event')"
                                      x-model.fill="sourceContent"
                                      x-on:change="parseSource()" />
                        <x-input-help>
                            {{ __('admin.Paste the request for appearance message here, and the system will attempt to parse the relevant event details based on the selected organization.') }}
                        </x-input-help>
                    </x-input-container>

                    <div x-show="mode != 'email'">

                        @include('pages.admin.events.inc.header')
                        @include('pages.admin.events.inc.schedule')
                        @include('pages.admin.events.inc.location')
                        @include('pages.admin.events.inc.contact-information')
                        @include('pages.admin.events.inc.character-requests-and-limits')
                        @include('pages.admin.events.inc.venue-permissions-and-amenities')
                        @include('pages.admin.events.inc.miscellaneous')

                    </div>
                </div>

                <x-submit-container>
                    <x-submit-button>
                        {{ __('admin.Create') }}
                    </x-submit-button>
                    @if($event->organization_id > 0)
                        <x-link-button-cancel :url="route('admin.events.list', ['organization_id' => $event->organization_id])" />
                    @else
                        <x-link-button-cancel :url="route('admin.events.list')" />
                    @endif
                </x-submit-container>

            </form>
        </x-card>

    </x-slim-container>

    <x-modal-picker :label="__('admin.Select an Organization')" />

@endsection

@section('page-script')
    <script>
        window.$organization_hierarchy = @json($organization_hierarchy);
    </script>
@endsection