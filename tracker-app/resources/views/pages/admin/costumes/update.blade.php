@extends('layouts.base')

@section('page-title', 'Update Costume')

@section('content')

    @include('pages.admin.costumes.tabs', compact('costume'))

    <x-transmission-bar :id="'costume'" />

    <x-slim-container>

        <x-card>

            <form method="POST"
                  novalidate="novalidate">
                @csrf
                <x-input-container>
                    <x-label>
                        Name:
                    </x-label>
                    <x-input-text :property="'name'"
                                  :value="$costume->name" />
                </x-input-container>

                <x-submit-container>
                    <x-submit-button>
                        Update
                    </x-submit-button>
                    <x-link-button-cancel :url="route('admin.costumes.list')" />
                </x-submit-container>

            </form>

            <x-trooper-stamps :model="$costume" />
        </x-card>

    </x-slim-container>

@endsection