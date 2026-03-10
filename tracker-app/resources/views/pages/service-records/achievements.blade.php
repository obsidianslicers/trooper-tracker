@extends('layouts.base')

@section('page-title', 'Achievements')

@section('content')

    <div class="container-fluid py-4">
        <div class="row g-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0 text-uppercase fw-bold">
                    <i class="fa-brands fa-empire me-2"></i>
                </h2>

                <x-lookback :days="$days" />
            </div>

        </div>
    </div>

    <x-table>
        <thead>
            <tr>
                <th>Trooper</th>
                <th>Achievement</th>
                <th>Earned On</th>
            </tr>
        </thead>
        @forelse($trooper_achievements as $trooper_achievement)
            <tr>
                <td>{{ $trooper_achievement->trooper->display_name }}</td>
                <td>
                    <i class="fa fa-fw {{ $trooper_achievement->type->toIcon() }} me-2"></i>
                    {{ $trooper_achievement->type->toDescription() }}
                </td>
                <td>{{ $trooper_achievement->earned_on->format('M j, Y') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3"
                    class="text-center">
                    No achievements found in the last {{ $days }} days.
                </td>
            </tr>
        @endforelse
    </x-table>

@endsection