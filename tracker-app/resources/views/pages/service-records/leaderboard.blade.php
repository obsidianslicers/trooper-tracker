@extends('layouts.base')

@section('page-title', 'Leaderboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0 text-uppercase fw-bold">
                <i class="fa-brands fa-empire me-2"></i>
            </h2>

            <x-lookback :days="$days" />
        </div>
        
        <div class="col-xl-4 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-chart-pie me-2"></i> Organization Dominance
                </div>
                <div class="card-body">
                    @foreach($leaderboard['dominance'] as $organization)
                        <div class="mb-3">
                            @include('pages.events.inc.leaderboard-item', [
                                'label' => $organization->name,
                                'count' => $organization->events_count,
                                'count_of' => 'EVENTS',
                                'max' => $leaderboard['dominance']->max('events_count') ?: 1,
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-uniform-martial-arts me-2"></i> Top 5 Deployed Costumes
                </div>
                <div class="card-body">
                    @foreach($leaderboard['diversity'] as $kit)
                        <div class="mb-4">
                            @include('pages.events.inc.leaderboard-item', [
                                'label' => $kit['name'],
                                'count' => $kit['count'],
                                'count_of' => 'DEPLOYMENTS',
                                'max' => $leaderboard['diversity']->first()['count'] ?: 1,
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-bolt me-2"></i> Top 5 Trooopers
                </div>
                <div class="card-body">
                    @foreach($leaderboard['operatives'] as $op)
                        <div class="mb-4">
                            @include('pages.events.inc.leaderboard-item', [
                                'label' => $op->trooper->display_name,
                                'count' => $op->troop_count,
                                'count_of' => 'TROOPS',
                                'max' => $leaderboard['operatives']->first()->troop_count ?: 1,
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>
@endsection