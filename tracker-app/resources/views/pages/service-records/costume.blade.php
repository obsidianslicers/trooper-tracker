@extends('layouts.base')

@section('page-title', $costume->name . ' — Costume Leaderboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">

        {{-- Header --}}
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-2">
            <div>
                <a href="{{ route('service-records.costumes', ['days' => $days]) }}"
                   class="text-muted text-decoration-none small text-uppercase fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> Costume Arsenal
                </a>
                <h2 class="h4 mb-0 text-uppercase fw-bold mt-1">
                    <i class="fa-solid fa-shield-halved me-2"></i> {{ $costume->name }}
                </h2>
            </div>

            <div class="d-flex flex-wrap gap-3 align-items-end">
                <form method="GET" action="{{ route('service-records.costume', $costume) }}">
                    @if($days !== null)
                        <input type="hidden" name="days" value="{{ $days }}" />
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

        {{-- Stats bar --}}
        <div class="col-12">
            <div class="row g-3">
                <div class="col-sm-4">
                    <div class="card shadow-sm border-0 text-center py-3">
                        <div class="h3 fw-bold text-primary mb-0">
                            {{ number_format($stats['total_deployments']) }}
                        </div>
                        <div class="text-uppercase text-muted small fw-bold">Total Deployments</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm border-0 text-center py-3">
                        <div class="h3 fw-bold text-info mb-0">
                            {{ number_format($stats['unique_troopers']) }}
                        </div>
                        <div class="text-uppercase text-muted small fw-bold">Unique Troopers</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm border-0 text-center py-3">
                        <div class="h3 fw-bold text-success mb-0">
                            {{ $stats['last_deployed_at'] ? $stats['last_deployed_at']->format('M j, Y') : '—' }}
                        </div>
                        <div class="text-uppercase text-muted small fw-bold">Last Deployed</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Troopers --}}
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-bolt me-2"></i> Top Troopers — {{ $costume->name }}
                </div>
                @if($top_troopers->isNotEmpty())
                    @php
                        $max_troops = $top_troopers->first()->troop_count ?? 1;
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase small text-muted">
                                    <th scope="col" class="ps-3" style="width: 4rem;">Rank</th>
                                    <th scope="col">Trooper</th>
                                    <th scope="col" class="text-end" style="width: 8rem;">Troops</th>
                                    <th scope="col" class="pe-3" style="width: 35%;">Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_troopers as $op)
                                    <tr>
                                        <td class="ps-3 text-muted fw-bold">
                                            #{{ $loop->iteration }}
                                        </td>
                                        <td>
                                            <a href="{{ route('service-records.trooper', ['trooper' => $op->trooper]) }}"
                                               class="fw-bold text-uppercase text-decoration-none">
                                                {{ $op->trooper->display_name }}
                                            </a>
                                        </td>
                                        <td class="text-end text-primary fw-bold">
                                            {{ $op->troop_count }}
                                            <small class="text-muted" style="font-size: 0.6rem;">TROOPS</small>
                                        </td>
                                        <td class="pe-3">
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-success"
                                                     role="progressbar"
                                                     style="width: {{ ($op->troop_count / $max_troops) * 100 }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body">
                        <p class="text-muted mb-0">No trooper data found for the selected filters.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
