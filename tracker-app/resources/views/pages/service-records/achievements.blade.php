@extends('layouts.base')

@section('page-title', 'Achievements')

@section('content')
    <div class="container-fluid py-4">
        <!-- Responsive Header: Clean layout split on small viewports -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <h2 class="h4 mb-0 text-uppercase fw-bold text-white">
                <i class="fa-brands fa-empire me-2"></i> Achievements
            </h2>
            <div class="align-self-start align-self-sm-auto">
                <x-lookback :days="$days" />
            </div>
        </div>

        @if($trooper_achievements->isNotEmpty())

            <!-- 1. DESKTOP VIEW: Clean Data Table (Visible on MD screens and up) -->
            <div class="d-none d-md-block">
                <x-table>
                    <thead>
                        <tr>
                            <th>Trooper</th>
                            <th>Achievement</th>
                            <th>Earned On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trooper_achievements as $trooper_achievement)
                            <tr>
                                <td>
                                    <a href="{{ route('service-records.trooper', ['trooper' => $trooper_achievement->trooper]) }}"
                                       class="fw-semibold">
                                        {{ $trooper_achievement->trooper->display_name }}
                                    </a>
                                </td>
                                <td>
                                    <i class="fa fa-fw {{ $trooper_achievement->type->toIcon() }} me-2 text-warning"></i>
                                    {{ $trooper_achievement->type->toDescription() }}
                                </td>
                                <td class="text-nowrap">{{ $trooper_achievement->achievement_date->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>

            <!-- 2. MOBILE VIEW: Icon-Featured Stacked Cards (Visible only on screens smaller than MD) -->
            <div class="d-block d-md-none">
                <div class="row g-3">
                    @foreach($trooper_achievements as $trooper_achievement)
                        <div class="col-12">
                            <div class="card bg-dark border-secondary shadow-sm">
                                <div class="card-body p-3">

                                    <!-- Top Row: Icon badge + Trooper Link -->
                                    <div class="d-flex align-items-center mb-3">
                                        <!-- Achievement Icon Box -->
                                        <div class="flex-shrink-0 d-flex align-items-center justify-content-center bg-secondary-subtle rounded text-warning fs-4"
                                             style="width: 42px; height: 42px;">
                                            <i class="fa {{ $trooper_achievement->type->toIcon() }}"></i>
                                        </div>
                                        <!-- Recipient Meta -->
                                        <div class="flex-grow-1 min-w-0 ms-3">
                                            <small class="text-muted d-block text-uppercase fw-bold text-xs">Trooper</small>
                                            <a href="{{ route('service-records.trooper', ['trooper' => $trooper_achievement->trooper]) }}"
                                               class="h6 mb-0 text-decoration-none fw-bold text-truncate d-block">
                                                {{ $trooper_achievement->trooper->display_name }}
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Bottom Row: Achievement Context & Date Stamp -->
                                    <div class="row g-2 pt-2 border-top border-secondary align-items-center">
                                        <div class="col-7">
                                            <small class="text-muted d-block text-uppercase fw-bold text-xs mb-0.5">Achievement Earned</small>
                                            <span class="text-white small fw-semibold d-block text-break">
                                                {{ $trooper_achievement->type->toDescription() }}
                                            </span>
                                        </div>
                                        <div class="col-5 text-end">
                                            <small class="text-muted d-block text-uppercase fw-bold text-xs mb-1">Date</small>
                                            <span class="badge bg-secondary text-nowrap">
                                                {{ $trooper_achievement->achievement_date->format('M j, Y') }}
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
            <!-- 3. EMPTY STATE: Standard fallback banner -->
            <div class="card bg-dark border-secondary text-center py-5 my-4">
                <div class="card-body">
                    <p class="text-muted mb-0">No achievements found in the last {{ $days }} days.</p>
                </div>
            </div>
        @endif
    </div>
@endsection