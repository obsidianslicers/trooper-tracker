@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>
    <p>
        A moderator has <strong>added you to {{ $organization->name }}</strong>.
        Your membership is now active and you'll appear on their roster.
    </p>
    <p>
        You can view your club memberships at any time from your
        <a href="{{ route('account.club-memberships') }}">account settings</a>.
    </p>

    @include('emails.inc.signature')

@endsection
