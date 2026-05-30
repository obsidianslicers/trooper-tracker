@extends('layouts.email')

@section('message')
    <p>
        Esteemed Command Staff,
    </p>

    <p>
        @if($action === 'cancelled')
            <strong>{{ $trooper->display_name }}</strong> has <strong>cancelled</strong> their attendance for the following deployment:
        @elseif($action === 'resigned_up')
            <strong>{{ $trooper->display_name }}</strong> has <strong>re-signed up</strong> for the following deployment:
        @else
            <strong>{{ $trooper->display_name }}</strong> has <strong>signed up</strong> for the following deployment:
        @endif
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
