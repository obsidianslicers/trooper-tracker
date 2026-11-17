@extends('layouts.base')

@section('page-title', __('admin.Update Event'))

@section('content')

    <x-transmission-bar :id="'event'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>
        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>{{ __('admin.Hosting Organization:') }}</x-label>
                    @if($can_change_host)
                        <x-input-picker :property="'organization_id'"
                                        :route="'pickers.organization'"
                                        :params="['event_host_only' => true]"
                                        :text="$event->organization->name ?? 'Select a Host'"
                                        :value="$event->organization_id" />
                    @else
                        <div class="form-control-plaintext">{{ $event->organization->name ?? '' }}</div>
                        <input type="hidden" name="organization_id" value="{{ $event->organization_id }}">
                    @endif
                </x-input-container>

                @include('pages.admin.events.inc.header')
                @include('pages.admin.events.inc.coordinator')
                @include('pages.admin.events.inc.schedule')
                @include('pages.admin.events.inc.location')
                @include('pages.admin.events.inc.contact-information')
                @include('pages.admin.events.inc.character-requests-and-limits')
                @include('pages.admin.events.inc.venue-permissions-and-amenities')
                @include('pages.admin.events.inc.miscellaneous')

                <x-submit-container>
                    <span class="float-start">
                        <a href="{{ route('events.display', compact('event')) }}"
                           class="btn btn-outline-info mb-2"
                           target="_blank">
                            {{ __('admin.View Event') }}
                            <span class="fa fa-fw fa-external-link"></span>
                        </a>
                    </span>
                    <x-submit-button>{{ __('admin.Update') }}</x-submit-button>
                    <x-link-button-cancel :url="route('admin.events.update', compact('event'))" />
                </x-submit-container>

                <x-trooper-stamps :model="$event" />
            </form>
        </x-card>
    </x-slim-container>

    <x-modal-picker :label="__('admin.Select an Organization')" />

@endsection