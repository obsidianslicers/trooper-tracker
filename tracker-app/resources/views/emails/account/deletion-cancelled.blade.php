@extends('layouts.email')

@section('message')
    <p>
        Trooper.
    </p>

    <p>
        Your account deletion request has been successfully cancelled.
        Your <b>{{ config('app.name') }}</b> account and all associated data
        remain intact and fully active.
    </p>

    <p>
        Welcome back to the ranks.
    </p>

    @include('emails.inc.signature')

@endsection
