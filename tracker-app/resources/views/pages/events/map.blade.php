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

    @if(TroopTracker::isSupportGoalEnabled())
        <x-card :label="'Support'">
            <div hx-get="{{ route('widgets.support-htmx') }}"
                 hx-trigger="load"
                 hx-swap="outerHTML">
                <x-loading />
            </div>
        </x-card>
    @endif

    <div x-data="Events.Search.mapSelector(@js($events))">
        <div class="d-flex align-items-center gap-2 mb-2"
             x-show="canLocate">
            <button type="button"
                    class="btn btn-sm btn-primary"
                    x-on:click="centerOnUser()"
                    x-bind:disabled="locating">
                <i class="fa fa-fw fa-location-crosshairs"
                   x-bind:class="locating && 'fa-beat-fade'"></i>
                Use Current Location
            </button>
            <span class="small text-warning"
                  x-show="locationError"
                  x-text="locationError"></span>
        </div>

        <div x-ref="map"
             style="height: 600px;">
        </div>
    </div>

@endsection