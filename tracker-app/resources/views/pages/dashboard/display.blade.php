@extends('layouts.base')

@section('page-title', 'Dashboard')

@section('content')

    @include('pages.dashboard.overview')
    @include('pages.dashboard.achievements', compact('milestones'))
    @include('pages.dashboard.organization-breakdown')
    @include('pages.dashboard.costume-breakdown')

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4"
        role="tablist">
        <li class="nav-item">
            <a class="nav-link active"
               data-bs-toggle="tab"
               href="#upcoming">
                Upcoming Troops
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               data-bs-toggle="tab"
               href="#history">
                Troop History
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               data-bs-toggle="tab"
               href="#awards">
                Awards
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               data-bs-toggle="tab"
               href="#synced-costumes">
                Synced Costumes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               data-bs-toggle="tab"
               href="#photos">
                Tagged Photos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               data-bs-toggle="tab"
               href="#donations">
                Support Donations
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">

        <!-- Donations & Support -->
        <div class="tab-pane fade show active"
             id="upcoming">
            <x-card :label="'Upcoming Troops'">
                <div hx-get="{{ route('dashboard.upcoming-troops-htmx', ['trooper_id' => $trooper->id]) }}"
                     hx-trigger="load"
                     hx-swap="outerHTML">
                    <x-loading />
                </div>
            </x-card>
        </div>

        <!-- Troop History -->
        <div class="tab-pane fade"
             id="history">
            <x-card :label="'Troop History'">
                <div hx-get="{{ route('dashboard.historical-troops-htmx', ['trooper_id' => $trooper->id]) }}"
                     hx-trigger="load"
                     hx-swap="outerHTML">
                    <x-loading />
                </div>
            </x-card>
        </div>

        <!-- Awards -->
        <div class="tab-pane fade"
             id="awards">
            <x-card :label="'Awards'">
                <div hx-get="{{ route('dashboard.awards-htmx', ['trooper_id' => $trooper->id]) }}"
                     hx-trigger="load"
                     hx-swap="outerHTML">
                    <x-loading />
                </div>
            </x-card>
        </div>

        <!-- Synced Costumes -->
        <div class="tab-pane fade"
             id="synced-costumes">
            <x-card :label="'Synced Costumes'">
                <div style="max-height:420px; overflow:auto;">
                    @forelse($synced_costumes as $sc)
                        @php
                            // collect large image and bucket-off variants (if present)
                            $images = collect();
                            foreach ($sc->trooper_costumes as $tc) {
                                if (! empty($tc->large_image_url)) { $images->push($tc->large_image_url); }
                                if (! empty($tc->bucket_off_url)) { $images->push($tc->bucket_off_url); }
                            }
                            $images = $images->filter()->unique()->values();
                        @endphp
                        <div class="mb-3">
                            <div class="small text-muted">{{ $sc->name }}</div>
                            <div class="d-flex gap-2 mt-1">
                                @foreach($images as $img)
                                    <a href="{{ $img }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ $img }}" alt="{{ $sc->name }}" class="img-fluid rounded" style="width:128px; height:128px; object-fit:cover;">
                                    </a>
                                @endforeach
                                @if($images->isEmpty())
                                    <div class="text-muted small">No images</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="small text-muted">No synced costumes for this trooper.</div>
                    @endforelse
                </div>
            </x-card>
        </div>

        <!-- Tagged Photos -->
        <div class="tab-pane fade"
             id="photos">
            <x-card :label="'Tagged Photos'">
                <div hx-get="{{ route('dashboard.tagged-uploads-htmx', ['trooper_id' => $trooper->id]) }}"
                     hx-trigger="load"
                     hx-swap="outerHTML">
                    <x-loading />
                </div>
            </x-card>
        </div>

        @if(config('tracker.support.url'))
            <!-- Donations -->
            <div class="tab-pane fade"
                 id="donations">
                <x-card :label="'Support Donations'">
                    <div hx-get="{{ route('dashboard.donations-htmx', ['trooper_id' => $trooper->id]) }}"
                         hx-trigger="load"
                         hx-swap="outerHTML">
                        <x-loading />
                    </div>
                </x-card>
            </div>
        @endif

    </div>

@endsection