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
                    <x-input-text :property="'organization_name'"
                                  :disabled="true"
                                  :value="$event->organization->name ?? 'Everyone'" />
                </x-input-container>

                @include('pages.admin.events.inc.header')
                @include('pages.admin.events.inc.location')
                @include('pages.admin.events.inc.schedule')
                @include('pages.admin.events.inc.contact-information')
                @include('pages.admin.events.inc.character-requests-and-limits')
                @include('pages.admin.events.inc.venue-permissions-and-amenities')
                @include('pages.admin.events.inc.charity')
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
                    @if($event->is_active)
                        <x-submit-button>Update</x-submit-button>
                    @endif
                    <x-link-button-cancel :url="route('admin.events.update', compact('event'))" />
                </x-submit-container>

                <x-trooper-stamps :model="$event" />
            </form>
        </x-card>
    </x-slim-container>

@endsection