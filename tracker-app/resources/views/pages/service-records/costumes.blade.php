@extends('layouts.base')

@section('page-title', 'Costume Arsenal')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">

        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-4">
            <h2 class="h4 mb-0 text-uppercase fw-bold">
                <i class="fa-solid fa-shield-halved me-2"></i> Costume Arsenal
            </h2>

            <x-lookback :days="$days" :all-time="true" />
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-ranking-star me-2"></i> All Costumes by Deployment
                </div>
                @if($costumes->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase small text-muted">
                                    <th scope="col" class="ps-3" style="width: 4rem;">Rank</th>
                                    <th scope="col">Costume</th>
                                    <th scope="col" class="text-end" style="width: 10rem;">Deployments</th>
                                    <th scope="col" class="text-end pe-3" style="width: 10rem;">Unique Troopers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($costumes as $entry)
                                    <tr>
                                        <td class="ps-3 text-muted fw-bold">
                                            #{{ $loop->iteration }}
                                        </td>
                                        <td>
                                            <a href="{{ route('service-records.costume', ['costume' => $entry->costume->id, 'days' => $days]) }}"
                                               class="fw-bold text-uppercase text-decoration-none">
                                                {{ $entry->costume->name }}
                                            </a>
                                        </td>
                                        <td class="text-end text-primary fw-bold">
                                            {{ number_format($entry->deployment_count) }}
                                            <small class="text-muted" style="font-size: 0.6rem;">TROOPS</small>
                                        </td>
                                        <td class="text-end pe-3 text-info fw-bold">
                                            {{ number_format($entry->unique_troopers) }}
                                            <small class="text-muted" style="font-size: 0.6rem;">TROOPERS</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body">
                        <p class="text-muted mb-0">No costume deployments found for the selected time period.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
