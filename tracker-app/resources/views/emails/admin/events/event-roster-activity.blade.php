@extends('layouts.email')

@section('message')
    <p>
        Esteemed Command Staff,
    </p>

    <p>
        @if($action === \App\Enums\RosterAction::CANCELLED)
            <strong>{{ $trooper->display_name }}</strong> has <strong>cancelled</strong> their attendance for the following deployment:
        @elseif($action === \App\Enums\RosterAction::RESIGNED_UP)
            <strong>{{ $trooper->display_name }}</strong> has <strong>re-signed up</strong> for the following deployment:
        @else
            <strong>{{ $trooper->display_name }}</strong> has <strong>signed up</strong> for the following deployment:
        @endif
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        {{ $event_trooper->event_shift->time_display }}
    </p>

    <p>
        <a href="{{ route('events.display', compact('event')) }}">
            {{ route('events.display', compact('event')) }}
        </a>
    </p>

    @include('emails.inc.signature')

@endsection
