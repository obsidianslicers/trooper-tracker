@extends('layouts.base')

@section('page-title', 'Trooper Approvals')

@section('content')
    <h5>Trooper Approvals</h5>
    <p class="text-muted small mb-3">New troopers awaiting review and activation.</p>

    <x-transmission-bar :id="'approvals'" />

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
        @forelse ($troopers as $trooper)
            <div class="col">
                @include('pages.admin.troopers.approval-card', compact('trooper'))
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
                <p class="text-muted mb-0">No pending trooper approvals</p>
            </div>
        @endforelse
    </div>

    <h5 class="mt-5">Club Join Requests</h5>
    <p class="text-muted small mb-3">Troopers requesting membership in external organizations.</p>

    <x-transmission-bar :id="'trooper-requests'" />

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
        @forelse($trooper_requests as $trooper_request)
            <div class="col">
                @include('pages.admin.troopers.trooper-request-card', compact('trooper_request'))
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
                <p class="text-muted mb-0">No pending club join requests</p>
            </div>
        @endforelse
    </div>

@endsection