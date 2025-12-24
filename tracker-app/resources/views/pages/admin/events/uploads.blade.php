@extends('layouts.base')

@section('page-title', 'Event Uploads')

@section('content')

    <x-transmission-bar :id="'event'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>

        <x-card>
        </x-card>

    </x-slim-container>

@endsection