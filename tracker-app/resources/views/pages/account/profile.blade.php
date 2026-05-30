@extends('layouts.base')

@section('page-title', 'Trooper Profile')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Email:
                    </x-label>
                    <x-input-text :property="'email'"
                                  :value="$trooper->email"
                                  :disabled="true" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Legal Name:
                    </x-label>
                    <x-input-text :property="'legal_name'"
                                  :value="$trooper->legal_name" />
                    <x-input-help>
                        Used for official records and communications with event staff and coordinators.
                        This will not be displayed publicly on your profile or on the dashboard, but will be
                        shared with event coordinators for safety and accountability reasons.
                    </x-input-help>
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Display Name:
                    </x-label>
                    <x-input-text :property="'display_name'"
                                  :value="$trooper->display_name" />
                    <x-input-help>
                        This is the name that will be shown publicly on your profile and on the dashboard.
                    </x-input-help>
                </x-input-container>

                <x-input-container x-data>
                    <x-label>
                        Phone (Optional):
                    </x-label>
                    <x-input-text :property="'phone'"
                                  :value="$trooper->phone"
                                  x-mask="(999) 999-9999" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Theme:
                    </x-label>
                    <x-input-select :property="'theme'"
                                    :options="\App\Enums\TrooperTheme::toArray()"
                                    :value="$trooper->theme->value" />
                </x-input-container>

                @if($costume_options->isNotEmpty())
                <x-input-container>
                    <x-label>
                        Display ID Prefix:
                    </x-label>
                    <x-input-select :property="'display_costume_id'"
                                    :options="['' => '— Auto (first costume) —'] + $costume_options->toArray()"
                                    :value="$trooper->display_costume_id ?? ''" />
                    <x-input-help>
                        Choose which prefix and ID to display on the forum (e.g. TK52233 or SL52233).
                        Leave blank to use the first costume automatically.
                    </x-input-help>
                </x-input-container>
                @endif

                <x-submit-container>
                    <span class="float-start">
                        <a href="{{ route('service-records.trooper', compact('trooper')) }}"
                           class="btn btn-outline-info mb-2">
                            Service Record
                        </a>
                    </span>
                    <x-submit-button>
                        Update
                    </x-submit-button>
                </x-submit-container>
            </form>

        </x-card>

    </x-slim-container>

@endsection
