@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>

    <p>
        Your request to join <strong>{{ $organization->name }}</strong> has been
        <strong>approved</strong>. Someone in Command actually reviewed your file and said yes.
    </p>

    <p>
        Your membership is now active, which means you'll appear on the roster for upcoming
        events. Congratulations — you're officially part of the chaos now.
    </p>

    <p>
        You may review your status at any time by visiting your
        <a href="{{ route('account.club-memberships') }}">club memberships</a>.
        Try not to break anything.
    </p>

    @include('emails.inc.signature')

@endsection
