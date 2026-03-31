@extends('layouts.base')

@section('page-title', 'Trooper Approvals')

@section('content')
    <x-transmission-bar :id="'approvals'" />

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
        @foreach ($troopers as $trooper)
            <div class="col">
                @include('pages.admin.troopers.approval-card', compact('trooper'))
            </div>
        @endforeach
    </div>
@endsection