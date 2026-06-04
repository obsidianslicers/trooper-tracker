@extends('layouts.base')

@section('page-title', 'Event Uploads')

@section('content')

    <x-transmission-bar :id="'event'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>

        <div id="event-uploads">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                @foreach($event->event_uploads as $event_upload)
                    @if($event_upload->is_administrative)
                        <div class="col">
                            <div class="card h-100">
                                <img src="{{ $event_upload->small_url }}"
                                     class="card-img-top rounded"
                                     alt="Upload {{ $event_upload->id }}" />
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <x-transmission-bar :id="'upload-images'" />
        <div id="admin-event-upload-messages"></div>

        <div class="upload-zone border border-secondary rounded p-5 text-center bg-light"
             hx-post="{{ route('admin.events.upload-image', compact('event')) }}"
             hx-trigger="submit"
             hx-select="#event-uploads"
             hx-target="#event-uploads"
             hx-swap="outerHTML"
             hx-include="input[type=file]"
             hx-encoding="multipart/form-data"
             data-flash-target="#admin-event-upload-messages"
             hx-indicator="#transmission-bar-upload-images">
            <input type="file"
                   name="images[]"
                   class="image-input d-none"
                   multiple
                   accept=".jpg,.jpeg,.png,.webp" />
            <div id="upload-label"
                 class="text-muted">
                Drag & drop JPG, PNG, or WEBP images here, or click to upload
            </div>
        </div>

    </x-slim-container>

@endsection
