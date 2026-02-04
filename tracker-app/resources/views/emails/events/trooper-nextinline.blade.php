@extends('layouts.email')

@section('message')
    <p>
        Mission Update Trooper!
    </p>
    <p>
        A change in the deployment roster has occurred. Another trooper has withdrawn from the mission, and as a result,
        you have been automatically promoted from <b>stand-by</b> to <b>GOING</b>. Consider this an
        unexpected but fully authorized elevation in duty status. Imperial logistics assures us this was not due to
        "mysterious circumstances," despite their suspiciously vague paperwork.
    </p>
    <p>
        <b>IMPORTANT:</b> If you are unable to make the deployment, please upate your status on the
        <a href="{{ route('events.display', $event) }}#shift-{{ $event_shift->id }}">mission briefing page</a>
        - Imperial logistics prefers timely adjustments over last-minute excuses involving
        malfunctioning droids.
    </p>

    @include('emails.events.inc.details', compact('event', 'event_shift', 'link'))

    @include('emails.inc.signature')

@endsection