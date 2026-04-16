@extends('layouts.base')

@section('page-title', 'Service Record')

@section('content')

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="h1 mb-0 text-upper">
                {{ $trooper->display_name }}
                <span class="text-{{ $service_summary['rank_theme'] }} fw-bold text-uppercase small">
                    // {{ $service_summary['rank_title'] }}
                </span>
            </h2>
            @if($xenforo_group_banners->isNotEmpty())
                <div class="mt-2 service-record-banners">
                    @foreach($xenforo_group_banners as $banner)
                        {!! $banner['banner_text'] !!}
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            @include('pages.service-records.inc.trooper-service-summary')
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header border-bottom-0 p-0">
                    <ul class="nav nav-pills p-3 border-bottom"
                        id="dashboardTabs"
                        role="tablist">
                        <li class="nav-item"
                            role="presentation">
                            <button class="nav-link active text-uppercase small fw-bold"
                                    data-bs-toggle="tab"
                                    data-bs-target="#upcoming-shifts"
                                    aria-selected="true"
                                    role="tab">Upcoming</button>
                        </li>
                        <li class="nav-item"
                            role="presentation">
                            <button class="nav-link text-uppercase small fw-bold"
                                    data-bs-toggle="tab"
                                    data-bs-target="#recent-shifts"
                                    aria-selected="false"
                                    tabindex="-1"
                                    role="tab">History</button>
                        </li>
                        <li class="nav-item"
                            role="presentation">
                            <button class="nav-link text-uppercase small fw-bold"
                                    data-bs-toggle="tab"
                                    data-bs-target="#awards"
                                    aria-selected="false"
                                    tabindex="-1"
                                    role="tab">Awards</button>
                        </li>
                        <li class="nav-item"
                            role="presentation">
                            <button class="nav-link text-uppercase small fw-bold"
                                    data-bs-toggle="tab"
                                    data-bs-target="#costumes"
                                    aria-selected="false"
                                    tabindex="-1"
                                    role="tab">Costumes</button>
                        </li>
                        <li class="nav-item"
                            role="presentation">
                            <button class="nav-link text-uppercase small fw-bold"
                                    data-bs-toggle="tab"
                                    data-bs-target="#photos"
                                    aria-selected="false"
                                    tabindex="-1"
                                    role="tab">Photos</button>
                        </li>
                        @if(config('tracker.support.url'))
                            <li class="nav-item"
                                role="presentation">
                                <button class="nav-link text-uppercase small fw-bold"
                                        data-bs-toggle="tab"
                                        data-bs-target="#donations"
                                        aria-selected="false"
                                        tabindex="-1"
                                        role="tab">Support</button>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="card-body tab-content">
                    <div class="tab-pane fade show active"
                         id="upcoming-shifts"
                         role="tabpanel">
                        @include('pages.service-records.inc.trooper-upcoming-shifts')
                    </div>
                    <div class="tab-pane fade"
                         id="recent-shifts"
                         role="tabpanel">
                        @include('pages.service-records.inc.trooper-recent-shifts')
                    </div>
                    <div class="tab-pane fade"
                         id="awards"
                         role="tabpanel">
                        @include('pages.service-records.inc.trooper-awards')
                    </div>
                    <div class="tab-pane fade"
                         id="costumes"
                         role="tabpanel">
                        @if($trooper->is_handler)
                            Handlers don't typically have costumes, but they can still earn awards and serve in missions!
                        @else
                            @include('pages.service-records.inc.trooper-organizations')
                            <br />
                            @include('pages.service-records.inc.trooper-costumes')
                        @endif
                    </div>
                    <div class="tab-pane fade"
                         id="photos"
                         role="tabpanel">
                        @include('pages.service-records.inc.trooper-tagged-uploads')
                    </div>
                    @if(config('tracker.support.url'))
                        <div class="tab-pane fade"
                             id="donations"
                             role="tabpanel">
                            @include('pages.service-records.inc.trooper-donations')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

<style>
    .service-record-banners {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
        max-width: 100%;
    }

    .service-record-banners img {
        display: block;
        max-width: min(100%, 320px);
        max-height: 64px;
        width: auto;
        height: auto;
        object-fit: contain;
    }

    .service-record-banners > * {
        flex: 0 1 auto;
        max-width: 100%;
    }
</style>

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('serviceRecordPhotoModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var url = trigger.getAttribute('data-photo-url');
                var image = modal.querySelector('#serviceRecordPhotoModalImage');

                if (image && url) {
                    image.setAttribute('src', url);
                }
            });
        });
    </script>

    <div class="modal fade" id="serviceRecordPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content bg-dark border-0">
                <div class="modal-body p-0 position-relative">
                    <button type="button"
                            class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>

                    <img id="serviceRecordPhotoModalImage"
                         src=""
                         alt="Service record photo"
                         class="img-fluid w-100" />
                </div>
            </div>
        </div>
    </div>
@endsection