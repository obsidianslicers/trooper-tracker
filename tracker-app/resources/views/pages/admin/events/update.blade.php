@extends('layouts.base')

@section('page-title', 'Update Event')

@section('content')

    <x-transmission-bar :id="'event'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>
        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>Hosting Organization:</x-label>
                    <x-input-picker :property="'organization_id'"
                                    :route="'pickers.organization'"
                                    :params="['moderated_only' => true]"
                                    :text="$event->organization->name ?? 'Select a Host'"
                                    :value="$event->organization_id" />
                </x-input-container>

                @include('pages.admin.events.inc.header')
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
                            View Event
                            <span class="fa fa-fw fa-external-link"></span>
                        </a>
                    </span>
                    <x-submit-button>Update</x-submit-button>
                    <x-link-button-cancel :url="route('admin.events.update', compact('event'))" />
                </x-submit-container>

                <x-trooper-stamps :model="$event" />
            </form>
        </x-card>
    </x-slim-container>

    <x-modal-picker :label="'Select an Organization'" />

@endsection