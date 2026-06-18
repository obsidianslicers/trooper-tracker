@extends('layouts.base')

@section('page-title', 'Application Pending')

@section('content')

<x-transmission-bar :id="'pending'" />

<x-slim-container>

    <x-message type="warning"
               icon="fa-solid fa-clock"
               class="w-100">
        <strong>Your application is under review.</strong>
        <div class="mt-2">
            A moderator will review your submission and you will receive an email when a decision has been made.
        </div>
    </x-message>

</x-slim-container>

@endsection
