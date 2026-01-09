@extends('layouts.base')

@section('page-title', 'Create Costume')

@section('content')

    <x-transmission-bar :id="'organization'" />

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Organization:
                    </x-label>
                    <x-input-text :property="'organization_name'"
                                  :disabled="true"
                                  :value="$organization->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Costume:
                    </x-label>
                    <x-input-text :property="'name'" />
                </x-input-container>

                <x-submit-container>
                    <x-submit-button>
                        Create
                    </x-submit-button>
                    <x-link-button-cancel :url="route('admin.organizations.costumes', compact('organization'))" />
                </x-submit-container>

            </form>
        </x-card>

    </x-slim-container>

@endsection