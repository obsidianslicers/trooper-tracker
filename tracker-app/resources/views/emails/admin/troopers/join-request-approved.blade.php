@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>
    <p>
        Your request to join <strong>{{ $organization->name }}</strong> has been
        <strong>approved</strong>! You are now an official member of the unit.
    </p>
    <p>
        Your membership is active and you'll appear on the roster for upcoming events.
        Head to your <a href="{{ route('account.club-memberships') }}">club memberships</a>
        to see your current status.
    </p>

    @include('emails.inc.signature')

@endsection
