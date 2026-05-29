@extends('layouts.base')

@section('page-title', 'Leaderboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-4">
            <h2 class="h4 mb-0 text-uppercase fw-bold">
                <i class="fa-brands fa-empire me-2"></i>
            </h2>

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
        
        <div class="col-xl-4 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-chart-pie me-2"></i> Organization Activity
                </div>
                <div class="card-body">
                    @forelse($leaderboard['dominance'] as $organization)
                        <div class="mb-3">
                            @include('pages.events.inc.leaderboard-item', [
                                'label' => $organization->name,
                                'count' => $organization->events_count,
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

        <div class="col-xl-4 col-md-6">
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

        <div class="col-xl-4 col-md-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-bolt me-2"></i> Top 30 Troopers
                </div>
                <div class="card-body">
                    @php
                        $max_troops = $leaderboard['operatives']->first()->troop_count ?? 1;
                    @endphp
                    @forelse($leaderboard['operatives'] as $op)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('service-records.trooper', [
                                            'trooper' => $op->trooper,
                                        ]) }}"
                                       class="fw-bold small text-uppercase text-decoration-none">
                                        {{ $op->trooper->display_name }}
                                    </a>
                                </div>
                                <span class="text-primary fw-bold">
                                    {{ $op->troop_count }}
                                    <small class="text-muted"
                                           style="font-size: 0.6rem;">
                                        TROOPS
                                    </small>
                                </span>
                            </div>
                            <div class="progress"
                                 style="height: 6px;">
                                <div class="progress-bar bg-success"
                                     role="progressbar"
                                     style="width: {{
                                        ($op->troop_count / $max_troops) * 100
                                     }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">
                            No troopers found for the selected leaderboard filters.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
