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

    <x-event-cards>
        @foreach ($events as $event)
            <x-event-card :event="$event" />
        @endforeach
    </x-event-cards>

@endsection

@section('page-script')
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.card[data-route]');
            cards.forEach(function (card) {
                card.addEventListener('click', function () {
                    const route = card.getAttribute('data-route');
                    if (route) {
                        window.location.href = route;
                    }
                });
            });
        });
    </script>
@endsection