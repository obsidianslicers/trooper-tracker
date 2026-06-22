@extends('layouts.base')

@section('page-title', 'Inactive Account')

@section('content')

    <x-slim-container class="mt-4">

        <x-message>
            <p>
                PLEASE NOTE: Your account is currently inactive. If this is your first time using Troop Tracker, your account may not have been approved yet.
            </p>
            <p>
                If your registration was denied, log in to resubmit your application. If you believe this is an error, please contact an administrator for assistance.
            </p>
        </x-message>

    </x-slim-container>

@endsection