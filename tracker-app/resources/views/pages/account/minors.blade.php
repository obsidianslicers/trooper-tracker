@extends('layouts.base')

@section('page-title', 'Cadets')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>

        <x-card>

            <h6 class="mb-3">
                Cadet Accounts Assigned to You
            </h6>

            @if($minors->isEmpty())
                <p class="text-muted mb-0">
                    No cadet accounts are currently linked to you.
                </p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($minors as $minor)
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">
                                        <a href="{{ route('service-records.trooper', ['trooper' => $minor->id]) }}">
                                            {{ $minor->display_name }}
                                        </a>
                                    </div>
                                    <small class="text-muted">{{ $minor->legal_name }}</small>
                                </div>
                                <div class="text-end">
                                    @if($minor->date_of_birth)
                                        <small class="text-muted">
                                            DOB: {{ $minor->date_of_birth->format('M d, Y') }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

        </x-card>

    </x-slim-container>

@endsection