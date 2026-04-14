@extends('layouts.base')

@section('page-title', 'Share Event')

@section('page-meta')

    <meta property="og:title"
          content="{{ $event->name }}" />
    <meta property="og:description"
          content="{{ $event->name }} happening {{ $event->time_display }}. View details on the Troop Tracker." />
    <meta property="og:url"
          content="{{ route('shares.event', compact('event')) }}" />
    <meta property="og:type"
          content="event" />
    <meta property="article:published_time"
          content="{{ $event->created_at->toIso8601String() }}" />
    <meta property="article:modified_time"
          content="{{ $event->updated_at->toIso8601String() }}" />
    <meta name="twitter:card"
          content="summary_large_image">
    <meta name="twitter:title"
          content="{{ $event->name }}">
    <meta name="twitter:description"
          content="{{ $event->name }} happening {{ $event->time_display }}. View details on the Troop Tracker.">

    <meta name="twitter:image"
          content="{{ $image_url }}">
    <meta property="og:image"
          content="{{ $image_url }}" />

    @auth
        <meta http-equiv="refresh"
              content="0; url={{ route('events.display', $event) }}">
    @endauth

@endsection

@section('content')
    <x-slim-container>

        <x-message>
            This page is for members only. Please log in to view full details.
        </x-message>

    </x-slim-container>

@endsection