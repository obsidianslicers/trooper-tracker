@extends('layouts.base')

@section('page-title', 'Event Details')

@section('content')
<x-slim-container>

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
                            @php($link = $event->createCalendarLink())
                            <div class="btn-group">
                                <a href="{{ $link->google() }}"
                                   class="btn btn-outline-secondary"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Add to Google Calendar">
                                    <i class="fab fa-google"></i>
                                </a>
                                <a href="{{ $link->yahoo() }}"
                                   class="btn btn-outline-secondary"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Add to Yahoo Calendar">
                                    <i class="fab fa-yahoo"></i>
                                </a>
                                <a href="{{ $link->webOutlook() }}"
                                   class="btn btn-outline-secondary"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Add to Outlook Calendar">
                                    <i class="fab fa-microsoft"></i>
                                </a>
                                <a href="{{ $link->ics() }}"
                                   class="btn btn-outline-secondary"
                                   title="Add to Calendar (.ics)">
                                    <i class="fa fa-fw fa-calendar"></i>
                                </a>
                            </div>
                            @if(App\Facades\TroopTrackerFacade::isXenforoIntegrationConfigured() && !empty($event->thread_id) && !empty($event->post_id))
                                <a href="{{ config('services.xenforo.base_url') . '/posts/' . $event->post_id . '/' }}"
                                   class="btn btn-outline-secondary"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="View Forum Post">
                                    <i class="fa fa-fw fa-comments"></i>
                                </a>
                            @endif

                            {!! Share::page(route('shares.event', compact('event')), $event->name)->facebook()->twitter() !!}
                            @can('update', $event)
                                <div class="btn-group">
                                    <a href="{{ route('admin.events.update', compact('event')) }}"
                                       class="btn btn-outline-danger">
                                        <i class="fa fa-fw fa-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.events.copy', compact('event')) }}"
                                       class="btn btn-outline-danger">
                                        <i class="fa fa-fw fa-copy"></i>
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
                        @include('pages.events.inc.attendance-limits', compact('event'))
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        @include('pages.events.inc.requested-characters', compact('event'))
                    </div>
                </div>

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
                        <x-section-title>Mission Brief</x-section-title>
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
                    <x-section-title>Mission Review</x-section-title>
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

                @include('pages.events.inc.xenforo-comments', compact('event', 'xenforoBaseUrl', 'xenforoThreadPosts'))

            </div>
        </div>
    </div>

</x-slim-container>

@endsection