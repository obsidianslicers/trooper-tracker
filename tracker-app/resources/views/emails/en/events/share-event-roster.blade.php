@extends('layouts.email')

@section('message')

    <p>
        Hello,
    </p>

    <p>
        Thank you for coordinating the upcoming event. Our command staff has prepared a
        public-facing roster for your reference.
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        {{ $event->time_display }}
    </p>

    <p>
        You can access it here:
        <a href="{{ route('shares.roster', $event_share) }}">link</a>
    </p>

    <p>
        This roster is intended for your use in coordinating with your team and volunteers.
        Please do not share the link publicly.
    </p>

    <p>
        Best regards,<br />
        {{ $trooper->legal_name }}<br />
        Trooper Command Staff / Administration <br />
    </p>
@endsection