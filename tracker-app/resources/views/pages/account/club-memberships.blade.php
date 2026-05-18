@extends('layouts.base')

@section('page-title', 'Club Memberships')

@section('content')

    @include('pages.account.tabs')

    <x-transmission-bar :id="'club-memberships'" />

    <x-slim-container>
        <div id="club-membership-form">
            @if($available_clubs->isEmpty())
                <x-message type="info" icon="fa-solid fa-circle-info">
                    You are already a member of all available clubs, or no clubs are available to join at this time.
                </x-message>
            @else
                <x-card>
                    <p class="text-muted mb-3">
                        Select a club below and submit a request. A moderator will review and approve your membership.
                    </p>

                    @php
                        $grouped = $available_clubs->groupBy(fn($o) => $o->parent?->name ?? 'Other');
                    @endphp

                    <x-input-container>
                        <x-label>
                            Club / Unit:
                        </x-label>
                        <select name="organization_id"
                                id="organization_id"
                                class="form-select @error('organization_id') is-invalid @enderror">
                            <option value="">— Select a club —</option>
                            @foreach($grouped as $parent_name => $clubs)
                                <optgroup label="{{ $parent_name }}">
                                    @foreach($clubs as $club)
                                        <option value="{{ $club->id }}">{{ $club->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-input-error :property="'organization_id'" />
                    </x-input-container>

                    <x-input-container>
                        <x-label>
                            Club ID / Identifier (optional):
                        </x-label>
                        <input type="text"
                               name="identifier"
                               id="identifier"
                               class="form-control @error('identifier') is-invalid @enderror"
                               placeholder="e.g. TK-0000"
                               maxlength="64" />
                        <x-input-help>
                            Enter your club-specific member ID if you have one (e.g. TK number, garrison ID). Leave blank if unknown.
                        </x-input-help>
                        <x-input-error :property="'identifier'" />
                    </x-input-container>

                    <x-submit-container>
                        <button type="button"
                                class="btn btn-primary"
                                hx-post="{{ route('account.club-memberships-htmx') }}"
                                hx-include="#organization_id, #identifier"
                                hx-target="#club-membership-form"
                                hx-swap="innerHTML"
                                hx-indicator="#transmission-bar-club-memberships">
                            <i class="fa fa-fw fa-paper-plane"></i> Request to Join
                        </button>
                    </x-submit-container>
                </x-card>
            @endif
        </div>
    </x-slim-container>

@endsection
