@extends('layouts.email')

@section('message')
    <p>
        @if($to_coordinator ?? false)
            {{ __('xenforo::emails.Esteemed Event Coordinator,') }}
        @else
            {{ __('xenforo::emails.Esteemed Command Staff,') }}
        @endif
    </p>

    <p>
        {!! __('xenforo::emails.:name has posted a comment on the following deployment and is requesting command staff attention:', ['name' => '<strong>'.e($poster->display_name).'</strong>']) !!}
    </p>

    @if($to_coordinator ?? false)
        <p>
            {{ __("xenforo::emails.You're getting this as the event's coordinator.") }}
        </p>
    @endif

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
