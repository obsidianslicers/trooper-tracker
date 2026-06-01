@extends('layouts.email')

@section('message')
    <p>
        Esteemed Command Staff,
    </p>

    <p>
        <strong>{{ $poster->display_name }}</strong> has posted a comment on the following deployment and is requesting command staff attention:
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        <a href="{{ route('events.display', compact('event')) }}">
            {{ route('events.display', compact('event')) }}
        </a>
    </p>

    @include('emails.inc.signature')

@endsection
