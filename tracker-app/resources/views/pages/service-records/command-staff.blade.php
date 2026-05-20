@extends('layouts.base')

@section('page-title', 'Command Staff')

@section('content')
    <div class="container-fluid py-4">
        <!-- Cleaned up Header Block -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0 text-uppercase fw-bold text-white">
                <i class="fa-brands fa-empire me-2"></i> Command Staff
            </h2>
        </div>

        @if($troopers->isNotEmpty())

            <!-- 1. DESKTOP VIEW: Traditional Structured Table (Visible on MD screens and up) -->
            <div class="d-none d-md-block">
                <x-table>
                    <thead>
                        <tr>
                            <th>Trooper</th>
                            <th>Role</th>
                            <th>CS Organizations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($troopers as $trooper)
                            <tr>
                                <td>
                                    <a href="{{ route('service-records.trooper', compact('trooper')) }}"
                                       class="fw-semibold">
                                        {{ $trooper->display_name }}
                                    </a>
                                </td>
                                <td>{{ to_title($trooper->membership_role->name) }}</td>
                                <td>
                                    @if($trooper->cs_organizations?->isNotEmpty())
                                        <span class="text-break">
                                            {{ $trooper->cs_organizations->pluck('name')->implode(', ') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>

            <!-- 2. MOBILE VIEW: Stacked Card List (Visible only on screens smaller than MD) -->
            <div class="d-block d-md-none">
                <div class="row g-3">
                    @foreach($troopers as $trooper)
                        <div class="col-12">
                            <div class="card bg-dark border-secondary shadow-sm">
                                <div class="card-body p-3">
                                    <!-- Header Line: Name & Main Role -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <a href="{{ route('service-records.trooper', compact('trooper')) }}"
                                           class="h6 mb-0 text-decoration-none fw-bold">
                                            {{ $trooper->display_name }}
                                        </a>
                                        <span class="badge bg-secondary text-uppercase text-xs tracking-wider">
                                            {{ to_title($trooper->membership_role->name) }}
                                        </span>
                                    </div>

                                    <!-- Secondary Data Block: Organizations -->
                                    <div class="pt-2 border-top border-secondary mt-2">
                                        <small class="text-muted d-block text-uppercase fw-bold text-xs mb-1">
                                            CS Organizations
                                        </small>
                                        <p class="card-text text-white small mb-0 text-break">
                                            @if($trooper->cs_organizations?->isNotEmpty())
                                                {{ $trooper->cs_organizations->pluck('name')->implode(', ') }}
                                            @else
                                                <span class="text-muted small italic">None listed</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        @else
            <!-- 3. EMPTY STATE: Universal styling for both views if collection is blank -->
            <div class="card bg-dark border-secondary text-center py-5 my-4">
                <div class="card-body">
                    <p class="text-muted mb-0">No command staff found.</p>
                </div>
            </div>
        @endif
    </div>
@endsection