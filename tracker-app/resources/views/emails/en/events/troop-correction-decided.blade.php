@extends('layouts.email')

@section('message')
    @if($approved)
        <p>
            Good news: your request for credit for <b>{{ $event_name }}</b> was approved.
            The troop now counts toward your totals.
        </p>
    @else
        <p>
            Your request for credit for <b>{{ $event_name }}</b> was denied.
            If you think that's a mistake, contact your command staff.
        </p>
    @endif

    <p>
        <a href="{{ $event_url }}">View the event</a>
    </p>

    @include(email_view('inc.signature'))

@endsection
