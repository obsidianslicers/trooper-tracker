@extends('layouts.base')

@section('page-title', __('auth.Inactive Account'))

@section('content')

    <x-slim-container class="mt-4">

        <x-message>
            <p>
                {{ __('auth.PLEASE NOTE: Your account is currently inactive. If this is your first time using Troop Tracker, your account may not have been approved yet.') }}
            </p>
            <p>
                {{ __('auth.If your registration was denied, log in to resubmit your application. If you believe this is an error, please contact an administrator for assistance.') }}
            </p>
        </x-message>

    </x-slim-container>

@endsection