@extends('layouts.base')

@section('page-title', 'Trooper Approvals')

@section('content')
    <x-transmission-bar :id="'approvals'" />

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
        @forelse ($troopers as $trooper)
            <div class="col">
                @include('pages.admin.troopers.approval-card', compact('trooper'))
            </div>
        @empty
            <div class="col">
                <p class="text-success mb-0">No pending trooper approvals</p>
            </div>
        @endforelse
    </div>

    @if($join_requests->isNotEmpty())
        <h5 class="mt-5">Club Join Requests</h5>

        <x-transmission-bar :id="'join-requests'" />

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
            @foreach($join_requests as $join_request)
                <div class="col">
                    @include('pages.admin.troopers.join-request-card', compact('join_request'))
                </div>
            @endforeach
        </div>
    @endif

@endsection