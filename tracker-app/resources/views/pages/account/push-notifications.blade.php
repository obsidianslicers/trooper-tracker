@extends('layouts.base')

@section('page-title', 'Notifications')

@section('content')

    <x-slim-container>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Notifications</h4>
            @if($notifications->isNotEmpty())
                <form method="POST" action="{{ route('account.push-notifications.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        Clear all
                    </button>
                </form>
            @endif
        </div>

        @if($notifications->isEmpty())
            <x-card>
                <p class="text-muted mb-0">No notifications yet.</p>
            </x-card>
        @else
            <div class="list-group">
                @foreach($notifications as $notification)
                    <form method="POST"
                          action="{{ route('account.push-notifications.read', $notification) }}">
                        @csrf
                        <button type="submit"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3 text-start
                                       {{ $notification->isUnread() ? 'list-group-item-light fw-semibold' : '' }}">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($notification->isUnread())
                                        <span class="badge bg-primary rounded-pill" style="width: 8px; height: 8px; padding: 0;"></span>
                                    @else
                                        <span style="width: 8px; height: 8px; display: inline-block;"></span>
                                    @endif
                                    <span>{{ $notification->title }}</span>
                                </div>
                                <div class="text-muted fw-normal small mt-1 ms-3">{{ $notification->body }}</div>
                            </div>
                            <small class="text-muted text-nowrap fw-normal">
                                {{ $notification->created_at->diffForHumans() }}
                            </small>
                        </button>
                    </form>
                @endforeach
            </div>
        @endif

    </x-slim-container>

@endsection
