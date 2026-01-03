@extends('layouts.base')

@section('page-title', 'Trooper Memberships')

@section('content')

    @include('pages.admin.troopers.tabs', compact('trooper'))

    <x-card>

        <x-transmission-bar :id="'organizations'" />

        <form method="POST"
                novalidate="novalidate">
            @csrf

            <x-input-container>
                <x-label>
                    Trooper:
                </x-label>
                <x-input-text :property="'trooper_name'"
                                :disabled="true"
                                :value="$trooper->name" />
            </x-input-container>
            <x-table>
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Identifier</th>
                        <th>Region</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                @foreach ($organization_memberships as $organization)
                    <tr>
                        <td>
                            {{ $organization->name }}
                        </td>
                        <td>
                            <x-input-text :property="'organizations.' . $organization->id . '.identifier'"
                                          :value="$organization->identifier ?? null"
                                          class="form-control-sm" />
                        </td>
                        <td>
                            <x-input-select :property="'organizations.' . $organization->id . '.region_id'"
                                            :options="$organization->organizations->pluck('name', 'id')->toArray()"
                                            :placeholder="'-- Select your Region/Garrison --'"
                                            :value="$organization->region->id ?? null"
                                            class="form-select-sm"
                                            hx-post="{{ route('auth.register-htmx', ['organization'=>$organization->id]) }}"
                                            hx-select="#unit-container-{{ $organization->id }}"
                                            hx-target="#unit-container-{{ $organization->id }}"
                                            hx-swap="outerHTML"
                                            hx-trigger="change"
                                            hx-include="closest div"
                                            hx-indicator="#transmission-bar-organizations" />
                        </td>
                        <td id="unit-container-{{ $organization->id }}">
                            @if($organization->region === null)
                            -
                            @elseif($organization->region->organizations->count() > 0)
                            <x-input-select :property="'organizations.' . $organization->id . '.unit_id'"
                                            :options="$organization->region->organizations->pluck('name', 'id')->toArray()"
                                            :placeholder="'-- Select your Unit/Squad --'"
                                            :value="$organization->unit->id ?? null"
                                            class="form-select-sm" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <x-submit-container>
                <x-submit-button>
                    Update
                </x-submit-button>
                <x-link-button-cancel :url="route('admin.troopers.profile', compact('trooper'))" />
            </x-submit-container>

        </form>
    </x-card>

@endsection