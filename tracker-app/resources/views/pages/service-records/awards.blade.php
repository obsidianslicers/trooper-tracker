@extends('layouts.base')

@section('page-title', 'Awards')

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
                <th colspan="2">Organization</th>
                <th>Award</th>
                <th>Trooper</th>
                <th>Award Date</th>
            </tr>
        </thead>
        @forelse($award_troopers as $award_trooper)
            <tr>
                <td>
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
                    <a href="{{ route('service-records.trooper', ['trooper' => $award_trooper->trooper]) }}">
                        {{ $award_trooper->trooper->display_name }}
                    </a>
                </td>
                <td>{{ $award_trooper->award_date->format('M j, Y') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5"
                    class="text-center">
                    No awards found in the last {{ $days }} days.
                </td>
            </tr>
        @endforelse
    </x-table>

@endsection