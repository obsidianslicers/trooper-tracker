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

    <div class="row mb-3">
        <div class="col-sm-12 col-md-6">

            <input type="text"
                   name="search_term"
                   placeholder="Search Events"
                   class="form-control"
                   value="" />

        </div>
    </div>

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
        document.querySelector('input[name=search_term]').addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('[data-event-name]');
            cards.forEach(card => {
                const name = card.dataset.eventName.toLowerCase();
                if (query.length == 0) {
                    card.classList.remove('d-none');
                    return;
                }
                if (name.includes(query)) {
                    card.classList.remove('d-none');
                }
                else {
                    card.classList.add('d-none');
                }
            });
        });
    </script>
@endsection