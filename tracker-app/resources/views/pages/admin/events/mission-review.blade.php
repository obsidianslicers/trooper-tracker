@extends('layouts.base')

@section('page-title', 'Mission Review')

@section('content')

    <x-transmission-bar :id="'mission-review'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>

        @if($member_uploads->isEmpty())
            <p class="text-muted">No member photos have been uploaded for this event.</p>
        @else
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                @foreach($member_uploads as $event_upload)
                    <div class="col" id="upload-{{ $event_upload->id }}">
                        <div class="card h-100">
                            <img src="{{ $event_upload->small_url }}"
                                 class="card-img-top rounded-top"
                                 alt="Upload {{ $event_upload->id }}" />
                            <div class="card-body p-2">
                                <small class="text-muted d-block">{{ $event_upload->trooper->display_name }}</small>
                                @if($event_upload->troopers->isNotEmpty())
                                    <small class="text-muted d-block">
                                        Tagged: {{ $event_upload->troopers->pluck('display_name')->join(', ') }}
                                    </small>
                                @endif
                            </div>
                            <div class="card-footer p-2">
                                <form hx-post="{{ route('admin.events.uploads.delete', compact('event', 'event_upload')) }}"
                                      hx-target="#upload-{{ $event_upload->id }}"
                                      hx-swap="outerHTML"
                                      hx-indicator="#transmission-bar-mission-review"
                                      hx-confirm="Permanently delete this photo?">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm btn-danger w-100">
                                        <i class="fa fa-trash me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </x-slim-container>

@endsection
