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

    @unless($trooper->deletion_requested_at)
    <x-slim-container>

        <x-card>
            <h5 class="text-danger mb-3">Danger Zone</h5>

            <p class="text-muted mb-3">
                Permanently delete your account and remove your personal information.
                Event participation history is retained in anonymized form.
                This cannot be undone after the 30-day grace period.
            </p>

            <button type="button"
                    class="btn btn-outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#delete-account-modal">
                Delete Account
            </button>
        </x-card>

    </x-slim-container>

    <div class="modal fade"
         id="delete-account-modal"
         tabindex="-1"
         aria-labelledby="delete-account-modal-label"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content"
                 x-data="{ confirmed: false, input: '' }">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"
                        id="delete-account-modal-label">
                        Delete Account
                    </h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                        This will schedule your account for permanent deletion in <strong>30 days</strong>.
                        You may cancel during this window by logging back in.
                    </p>
                    <ul>
                        <li>Your name, email, phone, and login credentials will be removed.</li>
                        <li>Event participation records are kept in anonymized form.</li>
                        <li>This action is irreversible after 30 days.</li>
                    </ul>
                    <div class="mt-3">
                        <label class="form-label"
                               for="delete-confirm-input">
                            Type <strong>DELETE</strong> to confirm:
                        </label>
                        <input id="delete-confirm-input"
                               type="text"
                               class="form-control"
                               autocomplete="off"
                               x-model="input"
                               x-on:input="confirmed = (input === 'DELETE')" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <form method="POST"
                          action="{{ route('account.delete') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-danger"
                                x-bind:disabled="!confirmed">
                            Delete My Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endunless

@endsection
