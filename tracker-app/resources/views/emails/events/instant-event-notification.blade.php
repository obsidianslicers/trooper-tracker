@extends('layouts.email')

@section('message')

    <p>
        Mission directive, Trooper!
    </p>

    <p>
        Failure to review may result in additional "motivation."
    </p>

    <hr />
    @include('emails.events.inc.event-notification', compact('event', 'event_shifts'))

    @include('emails.inc.signature')

@endsection