@extends('layouts.base')

@section('page-title', 'Leaderboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <h2 class="h4 mb-0 text-uppercase fw-bold">
                    <i class="fa-brands fa-empire me-2"></i> Leaderboard
                </h2>

                @if($organization)
                    <div class="d-flex align-items-center gap-2">
                        <x-logo :storage_path="$organization->image_path_lg"
                                default_path="img/icons/organization-128x128.png"
                                :width="64"
                                :height="64" />
                        <div class="small text-uppercase fw-bold lh-sm">
                            {{ $organization->name }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="d-flex flex-wrap gap-3 align-items-end">
                <form method="GET"
                      action="{{ route('service-records.leaderboard') }}">
                    @if($days !== null)
                        <input type="hidden"
                               name="days"
                               value="{{ $days }}" />
                    @endif
                    <x-label value="Club" />
                    <x-input-select :property="'organization_id'"
                                    :placeholder="'All Clubs'"
                                    :value="$organization_id"
                                    :options="$organizations->pluck('name', 'id')->toArray()"
                                    onchange="this.form.submit()" />
                </form>

                <x-lookback :days="$days" :all-time="true" />
            </div>
        </div>
        
        <div class="col-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-bolt me-2"></i> Top 30 Troopers
                </div>
                @if($leaderboard['operatives']->isNotEmpty())
                    @php
                        $max_troops = $leaderboard['operatives']->first()->troop_count ?? 1;
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase small text-muted">
                                    <th scope="col"
                                        class="ps-3"
                                        style="width: 4rem;">
                                        Rank
                                    </th>
                                    <th scope="col">
                                        Trooper
                                    </th>
                                    <th scope="col"
                                        class="text-end"
                                        style="width: 8rem;">
                                        Troops
                                    </th>
                                    <th scope="col"
                                        class="pe-3"
                                        style="width: 35%;">
                                        Activity
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaderboard['operatives'] as $op)
                                    <tr>
                                        <td class="ps-3 text-muted fw-bold">
                                            #{{ $loop->iteration }}
                                        </td>
                                        <td>
                                            <a href="{{ route('service-records.trooper', [
                                                    'trooper' => $op->trooper,
                                                ]) }}"
                                               class="fw-bold text-uppercase text-decoration-none">
                                                {{ $op->trooper->display_name }}
                                            </a>
                                        </td>
                                        <td class="text-end text-primary fw-bold">
                                            {{ $op->troop_count }}
                                            <small class="text-muted"
                                                   style="font-size: 0.6rem;">
                                                TROOPS
                                            </small>
                                        </td>
                                        <td class="pe-3">
                                            <div class="progress"
                                                 style="height: 5px;">
                                                <div class="progress-bar bg-success"
                                                     role="progressbar"
                                                     style="width: {{
                                                        ($op->troop_count / $max_troops) * 100
                                                     }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            No troopers found for the selected leaderboard filters.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-chart-pie me-2"></i> Organization Activity
                </div>
                <div class="card-body">
                    @forelse($leaderboard['dominance'] as $dominant_organization)
                        <div class="mb-3">
                            @include('pages.events.inc.leaderboard-item', [
                                'label' => $dominant_organization->name,
                                'count' => $dominant_organization->events_count,
                                'count_of' => 'EVENTS',
                                'max' => $leaderboard['dominance']->max('events_count') ?: 1,
                            ])
                        </div>
                    @empty
                        <p class="text-muted mb-0">No organization activity found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-uniform-martial-arts me-2"></i> Top 5 Deployed Costumes
                </div>
                <div class="card-body">
                    @forelse($leaderboard['diversity'] as $kit)
                        <div class="mb-4">
                            @include('pages.events.inc.leaderboard-item', [
                                'label' => $kit['name'],
                                'count' => $kit['count'],
                                'count_of' => 'DEPLOYMENTS',
                                'max' => $leaderboard['diversity']->first()['count'] ?? 1,
                            ])
                        </div>
                    @empty
                        <p class="text-muted mb-0">No costume deployments found.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
