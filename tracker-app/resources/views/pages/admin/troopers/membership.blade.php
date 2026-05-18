@extends('layouts.base')

@section('page-title', 'Trooper Memberships')

@section('content')

    @include('pages.admin.troopers.tabs', compact('trooper'))

    <x-slim-container>

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
                                  :value="$trooper->display_name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Role:
                    </x-label>
                    <x-input-text :property="'trooper_role'"
                                  :disabled="true"
                                  :value="to_title($trooper->membership_role->name)" />
                </x-input-container>

                <x-table>
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th>Identifier</th>
                            <th>Member Of</th>
                            <th></th>
                        </tr>
                    </thead>
                    @foreach ($organization_memberships as $organization)
                        <tr>
                            <td>
                                {{ $organization->name }}
                            </td>
                            <td>
                                <x-input-text :property="'organizations.' . $organization->id . '.identifier'"
                                              :value="$organization->identifier ?? null" />
                            </td>
                            <td>
                                <x-input-picker :property="'organizations.' . $organization->id . '.assignment'"
                                                :route="'pickers.organization'"
                                                :params="['organization_id' => $organization->id]"
                                                :text="$organization->assignment->name ?? 'Member Of ...'"
                                                :value="$organization->assignment->id ?? null" />
                            </td>
                            <td class="text-end">
                                @if($organization->is_member)
                                    <button class="btn btn-outline-danger"
                                            type="submit"
                                            formaction="{{ route('admin.troopers.membership.remove', compact('trooper', 'organization')) }}"
                                            formmethod="POST">
                                        <i class="fa fw fa-times"></i>
                                        Remove
                                    </button>
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

    </x-slim-container>

    <x-modal-picker :label="'Select an Organization for Membership'" />

@endsection