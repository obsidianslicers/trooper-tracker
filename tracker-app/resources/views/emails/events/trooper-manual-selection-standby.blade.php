@extends('layouts.email')

@section('message')
    <p>
        Mission Update Trooper!
    </p>

    <p>
        Your deployment status has been updated from <b>GOING</b> to <b>stand-by</b> for this mission.
        This change was completed by <b>{{ $changed_by->display_name }}</b>.
    </p>

    <p>
        If your availability has changed, please review your sign-up on the
        <a href="{{ route('events.display', $event) }}#shift-{{ $event_shift->id }}">mission briefing page</a>.
    </p>

    @include('emails.events.inc.details', compact('event', 'event_shift', 'link'))

    @include('emails.inc.signature')
@endsection
