@extends('layouts.email')

@section('message')

    <p>
        Trooper,
    </p>

    <p>
        Imperial logistics has issued updated orders for a deployment you are monitoring.
        Review the changes carefully before the mission commences.
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        {{ $event->time_display }}
    </p>

    @if(array_intersect($changed_fields, ['venue', 'venue_address', 'venue_city', 'venue_state', 'venue_zip']))
        <p>
            <b>Updated Location:</b>
            {{ implode(', ', array_filter([$event->venue_address, $event->venue_city, $event->venue_state, $event->venue_zip])) }}
        </p>
    @endif

    <p>
        <a href="{{ route('events.display', compact('event')) }}">
            {{ route('events.display', compact('event')) }}
        </a>
    </p>

    @include('emails.inc.signature')

@endsection
