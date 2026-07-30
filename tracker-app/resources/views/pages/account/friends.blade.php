@extends('layouts.base')

@section('page-title', 'Friends')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>

        <x-card>
            <h6 class="mb-3">
                Friend Accounts Linked to You
            </h6>

            @if($friends->isEmpty())
                <p class="text-muted mb-0">
                    No friend accounts are currently linked to you.
                </p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($friends as $friend)
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">
                                        <a href="{{ route('service-records.trooper', ['trooper' => $friend->friend->id]) }}">
                                            {{ $friend->friend->display_name }}
                                        </a>
                                    </div>
                                    <small class="text-muted">{{ $friend->friend->legal_name }}</small>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

        </x-card>

    </x-slim-container>

@endsection