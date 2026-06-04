@extends('layouts.base')

@section('page-title', 'Event Uploads')

@section('content')

    <x-transmission-bar :id="'event'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>

        @include('pages.admin.events.inc.upload-list', compact('event'))

        <x-transmission-bar :id="'upload-images'" />

        <div class="upload-zone border border-secondary rounded p-5 text-center bg-light"
             hx-post="{{ route('admin.events.upload-image', compact('event')) }}"
             hx-trigger="submit"
             hx-select="#event-uploads"
             hx-target="#event-uploads"
             hx-swap="outerHTML"
             hx-include="input[type=file]"
             hx-encoding="multipart/form-data"
             hx-indicator="#transmission-bar-upload-images">
            <input type="file"
                   name="images[]"
                   class="image-input d-none"
                   multiple
                   accept="image/*" />
            <div id="upload-label"
                 class="text-muted">
                Drag & drop images here, or click to upload
            </div>
        </div>

    </x-slim-container>

@endsection