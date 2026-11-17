@extends('layouts.email')

@section('message')

    <p>
        {{ __('xenforo::emails.Trooper,') }}
    </p>

    <p>
        {{ __('xenforo::emails.A new reply has been posted in the forum thread for a deployment you are monitoring.') }}
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        {!! __('xenforo::emails.:name posted:', ['name' => '<b>'.e($username).'</b>']) !!}
    </p>

    <blockquote style="border-left: 3px solid #ccc; padding-left: 1em; margin: 0 0 1em; color: #555;">
        {{ \Illuminate\Support\Str::limit(strip_tags($post_body), 300) }}
    </blockquote>

    @if ($post_url)
        <p>
            <a href="{{ $post_url }}">{{ __('xenforo::emails.View the reply on the forum') }}</a>
        </p>
    @endif

    <p>
        <a href="{{ route('events.display', compact('event')) }}">
            {{ __('xenforo::emails.View event details') }}
        </a>
    </p>

    @include(email_view('inc.signature'))

@endsection
