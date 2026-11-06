@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>

    <p>
        Your <strong>{{ $costume_name }}</strong> costume has been <strong>approved</strong>
        for {{ $club_name }}. You can now sign up in it and troops will count toward
        {{ $club_name }} credit.
    </p>

    <p>
        You can see your costumes at any time in your
        <a href="{{ route('account.index') }}">account settings</a>.
    </p>

    @include('emails.inc.signature')

@endsection
