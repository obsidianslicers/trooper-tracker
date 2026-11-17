@extends('layouts.base')

@section('page-title', __('admin.Delete Costume'))

@section('content')

    @include('pages.admin.costumes.tabs', compact('costume'))

    <x-slim-container>

        <x-card>

            <p>{!! __('admin.Are you sure you want to delete :name?', ['name' => '<strong>'.e($costume->name).'</strong>']) !!}</p>

            @if($event_trooper_count > 0 || $trooper_costume_count > 0)
                <div class="alert alert-warning">
                    <ul class="mb-0">
                        @if($event_trooper_count > 0)
                            <li>
                                {{ trans_choice('admin.:count event registration will have its costume reset to N/A. Historical troop credit will be preserved.|:count event registrations will have their costume reset to N/A. Historical troop credit will be preserved.', $event_trooper_count) }}
                            </li>
                        @endif
                        @if($trooper_costume_count > 0)
                            <li>
                                {{ trans_choice('admin.:count trooper costume will be removed from trooper profiles.|:count trooper costumes will be removed from trooper profiles.', $trooper_costume_count) }}
                            </li>
                        @endif
                    </ul>
                </div>
            @endif

            <form method="POST">
                @csrf
                <x-submit-container>
                    <x-submit-button class="btn-danger">
                        {{ __('admin.Delete') }}
                    </x-submit-button>
                    <x-link-button-cancel :url="route('admin.costumes.list')" />
                </x-submit-container>
            </form>

        </x-card>

    </x-slim-container>

@endsection
