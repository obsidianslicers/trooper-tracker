@extends('layouts.base')

@section('page-title', 'Copy Event')

@section('content')

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

                <x-input-container>
                    <x-label>Name:</x-label>
                    <x-input-text :property="'name'"
                                  :value="$event->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>New Scheduled Start:</x-label>
                    <x-input-datetime :property="'event_start'"
                                      :value="$event->event_start" />
                </x-input-container>

                <x-submit-container>
                    <x-submit-button>Copy</x-submit-button>
                    <x-link-button-cancel :url="route('admin.events.update', compact('event'))" />
                </x-submit-container>

            </form>
        </x-card>
    </x-slim-container>

@endsection