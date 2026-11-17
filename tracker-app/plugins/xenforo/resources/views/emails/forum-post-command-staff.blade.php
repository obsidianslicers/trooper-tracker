@extends('layouts.email')

@section('message')
    <p>
        {{ __('xenforo::emails.Esteemed Command Staff,') }}
    </p>

    <p>
        {!! __('xenforo::emails.:name has posted a comment on the following deployment and is requesting command staff attention:', ['name' => '<strong>'.e($poster->display_name).'</strong>']) !!}
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        <a href="{{ route('events.display', compact('event')) }}">
            {{ route('events.display', compact('event')) }}
        </a>
    </p>

    @include(email_view('inc.signature'))

@endsection
