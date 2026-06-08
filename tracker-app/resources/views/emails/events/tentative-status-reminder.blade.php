@extends('layouts.email')

@section('message')

    <p>
        Attention, Trooper!
    </p>

    <p>
        Your status for the following mission is still <strong>tentative</strong>.
        With {{ $days_until }} day(s) remaining, your fellow troopers and command staff need a
        confirmed headcount. Please take a moment to update your status to either
        <strong>Going</strong> or <strong>Cancelled</strong>.
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        {{ $event->time_display }}
    </p>

    <p>
        <a href="{{ route('events.display', compact('event')) }}">
            {{ route('events.display', compact('event')) }}
        </a>
    </p>

    @include('emails.inc.signature')

@endsection
