@extends('layouts.base')

@section('page-title', 'Trooper Profile')

@section('content')

    @include('pages.admin.troopers.tabs', compact('trooper'))

    <x-slim-container>

        @if($trooper->membership_status === \App\Enums\MembershipStatus::VOID)
            <div class="alert alert-warning mb-3" role="alert">
                <strong>This account has been marked as created in error.</strong>
                It cannot log in and does not appear in event pickers or reports.
            </div>
        @endif

        @if($trooper->membership_status === \App\Enums\MembershipStatus::RIP)
            <div class="alert alert-dark border-warning mb-3" role="alert">
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
                            View Dashboard
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

        @can('void', $trooper)
            <x-card class="mt-3 border-danger">
                <h6 class="text-danger mb-3">Danger Zone</h6>
                <p class="text-muted small mb-3">
                    Mark this account as created in error. The account will be blocked from logging in
                    and will no longer appear in event sign-up pickers or reports.
                </p>
                <form method="POST" action="{{ route('admin.troopers.void', compact('trooper')) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Mark {{ $trooper->display_name }} as created in error?')">
                        Mark as Created in Error
                    </button>
                </form>
            </x-card>
        @endcan

        @can('unmarkRip', $trooper)
            <x-card class="mt-3 border-secondary">
                <h6 class="text-secondary mb-3">Remove R.I.P. Status</h6>
                <p class="text-muted small mb-3">
                    Remove the R.I.P. status and restore this account to pending.
                    Use only if this was marked in error.
                </p>
                <form method="POST" action="{{ route('admin.troopers.unrip', compact('trooper')) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        Remove R.I.P. Status
                    </button>
                </form>
            </x-card>
        @endcan

    </x-slim-container>

@endsection