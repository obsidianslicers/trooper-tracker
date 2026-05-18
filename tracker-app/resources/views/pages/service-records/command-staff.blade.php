@extends('layouts.base')

@section('page-title', 'Command Staff')

@section('content')
    <div class="container-fluid py-4">
        <div class="row g-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0 text-uppercase fw-bold">
                    <i class="fa-brands fa-empire me-2"></i>
                </h2>
            </div>

        </div>
    </div>

    <x-table>
        <thead>
            <tr>
                <th>Trooper</th>
                <th>Role</th>
                <th>CS Organizations</th>
            </tr>
        </thead>
        @forelse($troopers as $trooper)
            <tr>
                <td>
                    <a href="{{ route('service-records.trooper', compact('trooper')) }}">
                        {{ $trooper->display_name }}
                    </a>
                </td>
                <td>{{ to_title($trooper->membership_role->name) }}</td>
                <td>
                    @if($trooper->cs_organizations?->isNotEmpty())
                        {{ $trooper->cs_organizations->pluck('name')->implode(', ') }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3"
                    class="text-center">
                    No command staff found.
                </td>
            </tr>
        @endforelse
    </x-table>
@endsection