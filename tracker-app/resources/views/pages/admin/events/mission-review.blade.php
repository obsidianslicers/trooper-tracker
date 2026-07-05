@extends('layouts.base')

@section('page-title', 'Mission Review')

@section('content')

    <x-transmission-bar :id="'mission-review'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>

        <div id="mission-review-photos">
            @if($member_uploads->isEmpty())
                <p class="text-muted">No member photos have been uploaded for this event.</p>
            @else
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                    @foreach($member_uploads as $event_upload)
                        @include('pages.admin.events.inc.upload-card', compact('event', 'event_upload'))
                    @endforeach
                </div>
            @endif
        </div>

    </x-slim-container>

@endsection
