@extends('layouts.base')

@section('page-title', 'Delete Costume')

@section('content')

    @include('pages.admin.costumes.tabs', compact('costume'))

    <x-slim-container>

        <x-card>

            <p>Are you sure you want to delete <strong>{{ $costume->name }}</strong>?</p>

            @if($event_trooper_count > 0 || $trooper_costume_count > 0)
                <div class="alert alert-warning">
                    <ul class="mb-0">
                        @if($event_trooper_count > 0)
                            <li>
                                {{ $event_trooper_count }} {{ Str::plural('event registration', $event_trooper_count) }}
                                will have their costume reset to N/A. Historical troop credit will be preserved.
                            </li>
                        @endif
                        @if($trooper_costume_count > 0)
                            <li>
                                {{ $trooper_costume_count }} {{ Str::plural('trooper costume', $trooper_costume_count) }}
                                will be removed from trooper profiles.
                            </li>
                        @endif
                    </ul>
                </div>
            @endif

            <form method="POST">
                @csrf
                <x-submit-container>
                    <x-submit-button class="btn-danger">
                        Delete
                    </x-submit-button>
                    <x-link-button-cancel :url="route('admin.costumes.list')" />
                </x-submit-container>
            </form>

        </x-card>

    </x-slim-container>

@endsection
