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
                    @can('update', $event)
                        <div class="btn-group mb-2 ms-auto d-none d-md-flex">
                            <a href="{{ route('admin.events.update', compact('event')) }}"
                               class="btn border-white">
                                <i class="fa fa-fw fa-edit"></i>
                                Edit
                            </a>
                            <a href="{{ route('admin.events.copy', compact('event')) }}"
                               class="btn border-white">
                                <i class="fa fa-fw fa-copy"></i>
                                Copy
                            </a>
                        </div>
                    @endcan
                </div>
                <div class="card-body">
                    @can('update', $event)
                        <div class="row d-md-none mb-2">
                            <div class="col-12">
                                <div class="btn-group mb-2 ms-auto w-100">
                                    <a href="{{ route('admin.events.update', compact('event')) }}"
                                       class="btn border-white">
                                        <i class="fa fa-fw fa-edit"></i>
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.events.copy', compact('event')) }}"
                                       class="btn border-white">
                                        <i class="fa fa-fw fa-copy"></i>
                                        Copy
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endcan
                    <div class="row mb-2 border-bottom">
                        <div class="col-12 col-md-6 small text-muted pb-3">
                            <div class="row">
                                <div class="col-4 col-md-3">
                                    Hosted By:
                                </div>
                                <div class="col-8 col-md-9">
                                    {{ $event->organization->name }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 col-md-3">
                                    Status:
                                </div>
                                <div class="col-8 col-md-9">
                                    {{ to_title($event->status->name) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 text-end">
                            @include('pages.events.inc.shares', compact('event'))
                        </div>
                    </div>

                    <div class="row pb-3 mb-3 border-bottom">
                        <div class="col-12">
                            @if($event->event_shifts->count() == 1)
                                {{ $event->time_display }}
                            @else
                                {{ $event->full_date_display }}
                                <span class="text-muted d-block d-md-inline ps-0 ps-md-3 pt-1 pt-md-0">
                                    ** {{ $event->event_shifts->count() }} shifts available **
                                </span>
                            @endif
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

                    @if($event->comments)
                        <div class="mb-3"
                             x-data="{ expanded: false, get fullHeight() { return (this.$refs.content.scrollHeight+40) + 'px' } }">

                            <x-section-title>Mission Brief</x-section-title>

                            <div class="mission-brief-container position-relative overflow-hidden rounded pointer"
                                 x-on:click="expanded = !expanded"
                                 x-bind:class="expanded ? '' : 'scanlines-overlay border border-success border-opacity-25'"
                                 x-bind:style="expanded ? 'max-height: ' + fullHeight : 'max-height: 120px'">
                                <div x-ref="content"
                                     x-bind:class="expanded ? '' : 'terminal-green'">
                                    {!! \App\Helpers\SmiliesRenderer::render(Str::markdown($event->comments), $smilies) !!}
                                </div>

                                <div x-show="!expanded"
                                     class="position-absolute bottom-0 start-0 w-100"
                                     style="height: 40px; background: linear-gradient(transparent, #1a1a1a); z-index: 3; pointer-events: none;">
                                </div>
                            </div>
                            <br />

                            <button x-on:click="expanded = !expanded"
                                    class="btn btn-link btn-sm p-0 mt-1 text-decoration-none shadow-none fw-bold text-success">
                                <i class="fas ms-1 me-1"
                                   x-bind:class="expanded ? 'fa-terminal' : 'fa-unlock-alt'"></i>
                                <span x-text="expanded ? 'CLOSE SECURE FEED' : 'DECRYPT MISSION BRIEF'"></span>
                            </button>

                            @if($mission_brief_required)
                                <div class="mt-3">
                                    @if($has_mission_brief_ack)
                                        <x-message type="success">
                                            You have acknowledged this mission brief. You may sign up for available shifts below.
                                        </x-message>
                                    @else
                                        <x-message type="warning">
                                            You must review and acknowledge the mission brief before you can sign up for this deployment.
                                        </x-message>

                                        <div class="mt-2"
                                             x-show="expanded"
                                             x-cloak>
                                            <form method="POST"
                                                  action="{{ route('events.ack-mission-brief', compact('event')) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-success">
                                                    <i class="fa fa-fw fa-check me-1"></i>
                                                    I have read and understand the mission brief
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

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
                        <div id="mission-review-upload-messages"></div>

                        <div class="upload-zone border border-secondary rounded p-5 text-center bg-light"
                             hx-post="{{ route('events.upload-image', compact('event')) }}"
                             hx-trigger="submit"
                             hx-select="#event-uploads"
                             hx-target="#event-uploads"
                             hx-swap="outerHTML"
                             hx-include="input[type=file]"
                             hx-encoding="multipart/form-data"
                             data-flash-target="#mission-review-upload-messages"
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
                    </div>

                    @include('pages.events.inc.xenforo-comments', compact('event', 'xenforoBaseUrl', 'xenforoThreadPosts'))

                </div>
            </div>
        </div>

    </x-slim-container>

@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('eventUploadPhotoModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var url = trigger.getAttribute('data-photo-url');
                var image = modal.querySelector('#eventUploadPhotoModalImage');

                if (image && url) {
                    image.setAttribute('src', url);
                }
            });
        });
    </script>

    <div class="modal fade"
         id="eventUploadPhotoModal"
         tabindex="-1"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content bg-dark border-0">
                <div class="modal-body p-0 position-relative">
                    <button type="button"
                            class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>

                    <img id="eventUploadPhotoModalImage"
                         src=""
                         alt="Event photo"
                         class="img-fluid w-100" />
                </div>
            </div>
        </div>
    </div>
@endsection
