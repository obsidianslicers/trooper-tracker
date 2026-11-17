@extends('layouts.base')

@section('page-title', __('admin.Update Award'))

@section('content')

    @include('pages.admin.awards.tabs', compact('award'))

    <x-transmission-bar :id="'award'" />

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        {{ __('admin.Organization:') }}
                    </x-label>
                    <x-input-text :property="'organization_name'"
                                  :disabled="true"
                                  :value="$award->organization->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        {{ __('admin.Frequency:') }}
                    </x-label>
                    <x-input-select :property="'false'"
                                    :disabled="true"
                                    :value="$award->frequency->value"
                                    :options="\App\Enums\AwardFrequency::toArray()" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        {{ __('admin.Name:') }}
                    </x-label>
                    <x-input-text :property="'name'"
                                  :value="$award->name" />
                </x-input-container>

                <x-submit-container>
                    <x-submit-button>
                        {{ __('admin.Update') }}
                    </x-submit-button>
                    <x-link-button-cancel :url="route('admin.awards.list')" />
                </x-submit-container>

                <x-trooper-stamps :model="$award" />

            </form>
        </x-card>

    </x-slim-container>

@endsection