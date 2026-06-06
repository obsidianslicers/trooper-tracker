@extends('layouts.email')

@section('message')

    <p>
        Trooper,
    </p>

    <p>
        A new reply has been posted in the forum thread for a deployment you are monitoring.
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        <b>{{ $username }}</b> posted:
    </p>

    <blockquote style="border-left: 3px solid #ccc; padding-left: 1em; margin: 0 0 1em; color: #555;">
        {{ \Illuminate\Support\Str::limit(strip_tags($message), 300) }}
    </blockquote>

    @if ($post_url)
        <p>
            <a href="{{ $post_url }}">View the reply on the forum</a>
        </p>
    @endif

    <p>
        <a href="{{ route('events.display', compact('event')) }}">
            View event details
        </a>
    </p>

    @include('emails.inc.signature')

@endsection
