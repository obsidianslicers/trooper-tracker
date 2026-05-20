@extends('layouts.base')

@section('page-title', 'Awards')

@section('content')
    <div class="container-fluid py-4">
        <!-- Responsive Header: Stack vertically on mobile, horizontal row on desktop -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <h2 class="h4 mb-0 text-uppercase fw-bold text-white">
                <i class="fa-brands fa-empire me-2"></i> Awards
            </h2>
            <div class="align-self-start align-self-sm-auto">
                <x-lookback :days="$days" />
            </div>
        </div>

        @if($award_troopers->isNotEmpty())

            <!-- 1. DESKTOP VIEW: Structured Data Table (Visible on MD screens and up) -->
            <div class="d-none d-md-block">
                <x-table>
                    <thead>
                        <tr>
                            <th colspan="2">Organization</th>
                            <th>Award</th>
                            <th>Trooper</th>
                            <th>Award Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($award_troopers as $award_trooper)
                            <tr>
                                <td style="width: 40px;">
                                    <x-logo :storage_path="$award_trooper->award->organization->image_path_sm"
                                            :default_path="'img/icons/organization-32x32.png'"
                                            :width="32"
                                            :height="32" />
                                </td>
                                <td>
                                    {{ $award_trooper->award->organization->name }}
                                </td>
                                <td>{{ $award_trooper->award->name }}</td>
                                <td>
                                    <a href="{{ route('service-records.trooper', ['trooper' => $award_trooper->trooper]) }}"
                                       class="fw-semibold">
                                        {{ $award_trooper->trooper->display_name }}
                                    </a>
                                </td>
                                <td class="text-nowrap">{{ $award_trooper->award_date->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>

            <!-- 2. MOBILE VIEW: Compact Visual Cards (Visible only on screens smaller than MD) -->
            <div class="d-block d-md-none">
                <div class="row g-3">
                    @foreach($award_troopers as $award_trooper)
                        <div class="col-12">
                            <div class="card bg-dark border-secondary shadow-sm">
                                <div class="card-body p-3">

                                    <!-- Top Section: Organization Logo & Main Recipient -->
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 me-3 bg-secondary-subtle p-1 rounded">
                                            <x-logo :storage_path="$award_trooper->award->organization->image_path_sm"
                                                    :default_path="'img/icons/organization-32x32.png'"
                                                    :width="40"
                                                    :height="40" />
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <small class="text-muted d-block text-uppercase fw-bold text-xs">Recipient</small>
                                            <a href="{{ route('service-records.trooper', ['trooper' => $award_trooper->trooper]) }}"
                                               class="h6 mb-0 text-decoration-none fw-bold text-truncate d-block">
                                                {{ $award_trooper->trooper->display_name }}
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Detail Grid: Award info and Date side by side -->
                                    <div class="row g-2 pt-2 border-top border-secondary">
                                        <div class="col-7">
                                            <small class="text-muted d-block text-uppercase fw-bold text-xs mb-0.5">Award / Unit</small>
                                            <span class="text-white small fw-semibold d-block text-break">
                                                {{ $award_trooper->award->name }}
                                            </span>
                                            <span class="text-muted text-xs d-block text-truncate">
                                                {{ $award_trooper->award->organization->name }}
                                            </span>
                                        </div>
                                        <div class="col-5 text-end">
                                            <small class="text-muted d-block text-uppercase fw-bold text-xs mb-0.5">
                                                Awarded
                                            </small>
                                            <span class="badge bg-secondary text-nowrap">
                                                {{ $award_trooper->award_date->format('M j, Y') }}
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        @else
            <!-- 3. EMPTY STATE: Standard fallback layout -->
            <div class="card bg-dark border-secondary text-center py-5 my-4">
                <div class="card-body">
                    <p class="text-muted mb-0">No awards found in the last {{ $days }} days.</p>
                </div>
            </div>
        @endif
    </div>
@endsection