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
        <a href="{{ route('account.index') }}">account settings</a>
        and selecting the memberships tab.
    </p>

    @include('emails.inc.signature')

@endsection