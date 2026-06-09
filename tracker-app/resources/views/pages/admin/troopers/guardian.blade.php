@extends('layouts.base')

@section('page-title', 'Guardian Info')

@section('content')

    @include('pages.admin.troopers.tabs', compact('trooper'))

    <x-slim-container>

        <x-card>

            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Date of Birth:
                    </x-label>
                    <x-input-text :property="'date_of_birth'"
                                  :type="'date'"
                                  :value="$trooper->date_of_birth?->format('Y-m-d')" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Guardian Email:
                    </x-label>
                    <x-input-text :property="'guardian_email'"
                                  :type="'email'"
                                  :value="$trooper->guardian?->email" />
                </x-input-container>

                <x-submit-container>
                    <x-submit-button>
                        Update
                    </x-submit-button>
                    <x-link-button-cancel :url="route('admin.troopers.list')" />
                </x-submit-container>

                <x-trooper-stamps :model="$trooper" />

            </form>

        </x-card>

    </x-slim-container>

@endsection
