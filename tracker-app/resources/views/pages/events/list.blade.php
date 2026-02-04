@extends('layouts.base')

@section('page-title', 'Upcoming Events')

@section('content')

    <div class="row p-3"
         hx-get="{{ route('widgets.notices-htmx') }}"
         hx-trigger="load"
         hx-swap="outerHTML">
        <div class="col text-center">
            <x-spinner />
        </div>
    </div>

    <x-card :label="'Support'">
        <div hx-get="{{ route('widgets.support-htmx') }}"
             hx-trigger="load"
             hx-swap="outerHTML">
            <x-loading />
        </div>
    </x-card>

    <div x-data="Events.Search.eventSelector()">

        @include('pages.events.inc.event-filters', compact('costume_organizations'))

        <div class="row row-cols-1 row-cols-md-3 g-4 mt-1 event-cards">

            @foreach ($events as $event)
                @include('pages.events.inc.event-card', compact('event'))
            @endforeach

        </div>

    </div>

@endsection