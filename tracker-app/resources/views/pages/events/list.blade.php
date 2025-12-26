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

@php($organization_id = 0)
<form method="GET"
      action="{{ route('events.list', ['organization_id' => $organization_id]) }}">
    <div class="row mb-3">

        <div class="col-sm-12 col-md-4">

            @foreach (qs() as $key => $value)
                <x-input-hidden :property="$key"
                                :value="$value" />
            @endforeach

            <x-input-container>
                <x-label>
                    Search Events:
                </x-label>
                <x-input-text :property="'search_term'"
                              :placeholder="'Search Event Name (at least 3 chars)'"
                              :value="$search_term" />
            </x-input-container>

        </div>
        <div class="col-sm-12 col-md-4">
            <x-input-container>
                <x-label>
                    Hosting Organization:
                </x-label>
                <x-input-picker :property="'organization_id'"
                                :route="'pickers.organization'"
                                :text="'Hosting Organization'"
                                :value="-1" />
            </x-input-container>
            <x-modal-picker :label="'Select an Organization'" />
        </div>
        <div class="col-sm-12 col-md-4">
            <x-input-container>
                <x-label>
                    Character Types:
                </x-label>
                <x-input-select :property="'organization_id'"
                                :options="$organizations->pluck('name', 'id')->toArray()"
                                :value="$selected_organization->id ?? -1"
                                :placeholder="'-- Requested Characters --'" />
            </x-input-container>
        </div>
        <div class="col-12 text-end">
            <x-submit-button>
                Apply Filters
            </x-submit-button>
        </div>

    </div>
</form>


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