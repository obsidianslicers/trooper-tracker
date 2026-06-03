@extends('layouts.base')

@section('page-title', $costume->name . ' — Costume Leaderboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">

        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-2">
            <div>
                <a href="{{ route('service-records.leaderboard', array_filter(['days' => $days, 'organization_id' => $organization_id])) }}"
                   class="text-muted text-decoration-none small text-uppercase fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> Leaderboard
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

        <div class="col-12">
            @include('pages.service-records.inc.costume-stats')
        </div>

    </div>
</div>
@endsection
