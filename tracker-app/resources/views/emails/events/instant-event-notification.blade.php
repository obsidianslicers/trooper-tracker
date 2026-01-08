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

    <p style="margin-top:20px; font-weight:bold; color:#333;">
        - Imperial Administration, {{ config('app.name') }}
    </p>
@endsection