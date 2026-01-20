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

        @foreach ($months as $month)
            <h3 class="mt-5">{{ $month['date']->format('F Y') }}</h3>

            <x-table class="event-calendar">
                <thead>
                    <tr>
                        <th class="text-center">Sun</th>
                        <th class="text-center">Mon</th>
                        <th class="text-center">Tue</th>
                        <th class="text-center">Wed</th>
                        <th class="text-center">Thu</th>
                        <th class="text-center">Fri</th>
                        <th class="text-center">Sat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($month['weeks'] as $week)
                        <tr>

                            @foreach ($week['days'] as $date)
                                <td data-event-date="{{ $date->toDateString() }}">

                                    <div class="calendar-content">

                                        <div class="float-end fw-bold small {{ $date->month !== $month['date']->month ? 'text-muted' : '' }}">
                                            {{ $date->format('j') }}
                                        </div>
                                        <br />

                                        @php $key = $date->toDateString(); @endphp

                                        @if (isset($events[$key]))
                                            @foreach ($events[$key] as $event)
                                                <div class="event-card small mt-1 p-1 pointer"
                                                     x-show="matches($el)"
                                                     data-route="{{ route('events.display', compact('event')) }}"
                                                     data-event-name="{{ $event->name }}"
                                                     data-event-hosting-organization-id="{{ $event->organization_id }}">

                                                    <x-logo :storage_path="$event->organization->image_path_sm ?? ''"
                                                            :default_path="'img/icons/organization-32x32.png'"
                                                            :width="16"
                                                            :height="16" />
                                                    <span class="ms-2">
                                                        {{ $event->name }}
                                                    </span>

                                                    @foreach ($event->organizations as $organization)
                                                        <span class="d-none"
                                                              data-event-costume-organization-id="{{ $organization->id }}"></span>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        @endif

                                    </div>
                                </td>
                            @endforeach

                        </tr>
                    @endforeach
                </tbody>
            </x-table>


        @endforeach

    </div>

@endsection

@section('page-script')
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.event-card[data-route]');
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