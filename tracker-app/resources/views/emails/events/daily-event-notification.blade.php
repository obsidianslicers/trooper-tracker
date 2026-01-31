@extends('layouts.email')

@section('message')

    <p>
        Mission alert, Trooper!
    </p>

    <p>
        Today's mission updates are below. Compliance is monitored. Enthusiasm is optional.
    </p>

    <hr />
    @foreach($event_notifications as $event_notification)
        @php
            $event = $event_notification->event;
            $event_shifts = $event->event_shifts;
        @endphp
        @include('emails.events.inc.event-notification', compact('event', 'event_shifts'))
    @endforeach

    @include('emails.inc.signature')

@endsection