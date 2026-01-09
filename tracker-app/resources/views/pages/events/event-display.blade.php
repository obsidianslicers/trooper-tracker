@extends('layouts.base')

@section('page-title', 'Event Sign-Up')

@section('content')
<x-slim-container>

    @php($bg = $event->at_risk ? 'bg-danger' : 'bg-primary')
    @php($bg = $event->is_locked ? 'bg-secondary' : $bg)
    <div class="container my-4">
        <div class="card">
            <div class="card-header {{ $bg }} d-flex align-items-center">
                <span class="p-2">
                    <x-logo :storage_path="$event->organization->image_path_sm ?? ''"
                            :default_path="'img/icons/organization-32x32.png'"
                            :width="32"
                            :height="32" />
                </span>
                <span class="p-2">
                    <h4 class=" text-white">
                        {{ $event->name }}
                    </h4>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-2 border-bottom">
                    <div class="col-8 small text-muted pb-3">
                        <div class="row">
                            <div class="col-3 d-none d-md-inline">
                                Hosted By:
                            </div>
                            <div class="col-9">
                                {{ $event->organization->name }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3 d-none d-md-inline">
                                Status:
                            </div>
                            <div class="col-9">
                                {{ to_title($event->status->name) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            {!! Share::page(route('share-event', compact('event')), $event->name)->facebook()->twitter() !!}
                            @can('update', $event)
                                <div class="btn-group">
                                    <a href="{{ route('admin.events.update', compact('event')) }}"
                                       class="btn btn-outline-danger">
                                        <i class="fa fa-fw fa-edit text-danger"></i>
                                    </a>
                                    <a href="{{ route('admin.events.copy', compact('event')) }}"
                                       class="btn btn-outline-danger">
                                        <i class="fa fa-fw fa-copy text-danger"></i>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="row pb-3 mb-3 border-bottom">
                    <div class="col-12">
                        {{ $event->time_display }}
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        @include('pages.events.inc.venue', compact('event'))
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        @include('pages.events.inc.contact', compact('event'))
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        @include('pages.events.inc.amenities', compact('event'))
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        @include('pages.events.inc.attendance', compact('event'))
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        @include('pages.events.inc.characters', compact('event'))
                    </div>
                </div>

                <x-modal-picker :label="'Find a Trooper'" />

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        @include('pages.events.inc.charity', compact('event'))
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        @include('pages.events.inc.shifts', compact('event'))
                    </div>
                </div>


                @if($event->comments)
                    <div class="mt-3">
                        <x-section-title>Comments</x-section-title>
                        <p>{!! Str::markdown($event->comments) !!}</p>
                    </div>
                @endif

                @if ($event->event_uploads->where('is_administrative', true)->isNotEmpty())
                    <div class="mt-5">
                        <x-section-title>Instructional Uploads</x-section-title>
                        @include('pages.events.inc.upload-display', ['event' => $event, 'is_administrative' => true])
                    </div>
                @endif

                <div class="mt-5">
                    <x-section-title>Event Uploads</x-section-title>
                    @include('pages.events.inc.upload-display', ['event' => $event, 'is_administrative' => false])
                    <hr />

                    <x-transmission-bar :id="'upload-images'" />

                    <div class="upload-zone border border-secondary rounded p-5 text-center bg-light"
                         hx-post="{{ route('events.upload-image', compact('event')) }}"
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
                </div>

            </div>
        </div>
    </div>

</x-slim-container>

@endsection