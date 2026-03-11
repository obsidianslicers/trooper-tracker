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