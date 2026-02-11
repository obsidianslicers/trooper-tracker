@extends('layouts.base')

@section('page-title', 'Award Trooper')

@section('content')

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Award:
                    </x-label>
                    <x-input-text :property="'award_name'"
                                  :disabled="true"
                                  :value="$award->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Organization:
                    </x-label>
                    <x-input-text :property="'organization_name'"
                                  :disabled="true"
                                  :value="$award->organization->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Award Date:
                    </x-label>
                    <x-input-date :property="'award_date'"
                                  :value="$award_date->format('Y-m-d')" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Trooper:
                    </x-label>
                    <x-input-picker :property="'trooper_id'"
                                    :route="'pickers.trooper'"
                                    :params="['moderated_only' => true, 'active_only' => true]"
                                    :text="'(Select a Trooper)'"
                                    :value="$award_trooper->trooper_id" />
                </x-input-container>

                <x-modal-picker :label="'Find a Trooper'" />

                <x-submit-container>
                    <x-submit-button>Submit</x-submit-button>
                    <x-link-button-cancel :url="route('admin.awards.list-troopers', compact('award'))" />
                </x-submit-container>

            </form>
        </x-card>
    </x-slim-container>

@endsection