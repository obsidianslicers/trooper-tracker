@extends('layouts.email')

@section('message')

    <p>
        Stand down, Trooper!
    </p>

    <p>
        Your mission has been cancelled.
        Use this unexpected free time wisely.
        Command will be evaluating your choices.
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

    <p style="margin-top:20px; font-weight:bold; color:#333;">
        - Imperial Administration, {{ config('app.name') }}
    </p>
@endsection