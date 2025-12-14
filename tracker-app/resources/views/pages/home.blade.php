@extends('layouts.base')

@section('page-title', setting('site_name'))

@section('content')

    {{--
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
    --}}

    <div class="row">
        <div class="col text-center">
            <img src="{{ url('img/logo.jpg') }}"
                 class="img-fluid" />
        </div>
    </div>
@endsection