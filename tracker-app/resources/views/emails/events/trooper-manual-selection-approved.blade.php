@extends('layouts.email')

@section('message')
    <p>
        Mission Update Trooper!
    </p>

    <p>
        You have been approved from <b>stand-by</b> to <b>GOING</b> for this deployment.
        Approval was completed by <b>{{ $approved_by->display_name }}</b>.
    </p>

    <p>
        If your availability has changed, please update your status on the
        <a href="{{ route('events.display', $event) }}#shift-{{ $event_shift->id }}">mission briefing page</a>.
    </p>

    @include('emails.events.inc.details', compact('event', 'event_shift', 'link'))

    @include('emails.inc.signature')
@endsection
