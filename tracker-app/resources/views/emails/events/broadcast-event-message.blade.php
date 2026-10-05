@extends('layouts.email')

@section('message')
    <p>
        Incoming transmission, Trooper.
    </p>

    <p>
        Command felt this information was important enough to interrupt whatever highly classified activity you were currently working on.
    </p>

    <p>
        Read carefully. There may be a quiz later. There definitely won't be a quiz later, but the disappointment will be real.
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <blockquote>
        {{ $message }}
    </blockquote>

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