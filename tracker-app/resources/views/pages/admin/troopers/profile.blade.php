@extends('layouts.base')

@section('page-title', 'Trooper Profile')

@section('content')

    @include('pages.admin.troopers.tabs', compact('trooper'))

    <x-slim-container>

        @if($trooper->membership_status === \App\Enums\MembershipStatus::INVALID)
            <div class="alert alert-warning mb-3"
                 role="alert">
                <strong>This account has been marked as created in error.</strong>
                It cannot log in and does not appear in event pickers or reports.
            </div>
        @endif

        @if($trooper->membership_status === \App\Enums\MembershipStatus::DEPARTED)
            <div class="alert alert-dark border-warning mb-3"
                 role="alert">
                <strong>This account is marked R.I.P. — In Memoriam.</strong>
                The service record displays a memorial tribute. The account cannot log in.
            </div>
        @endif

        <x-card>

            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Legal Name:
                    </x-label>
                    <x-input-text :property="'legal_name'"
                                  :value="$trooper->legal_name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Display Name:
                    </x-label>
                    <x-input-text :property="'display_name'"
                                  :value="$trooper->display_name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Last Active:
                    </x-label>
                    <x-input-text :property="'last_active_at'"
                                  :disabled="true"
                                  :value="$trooper->last_active_at?->format('D - M d, Y') ?? 'Never'" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Email:
                    </x-label>
                    <x-input-text :property="'email'"
                                  :value="$trooper->email" />
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
                        Status:
                    </x-label>
                    <x-input-select :property="'membership_status'"
                                    :options="\App\Enums\MembershipStatus::toArray()"
                                    :value="$trooper->membership_status->value" />
                </x-input-container>

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
                    <x-link-button-cancel :url="route('admin.troopers.list')" />
                </x-submit-container>

                <x-trooper-stamps :model="$trooper" />

            </form>

        </x-card>

    </x-slim-container>

@endsection