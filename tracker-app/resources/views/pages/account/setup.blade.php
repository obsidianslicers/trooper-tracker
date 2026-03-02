@extends('layouts.base')

@section('page-title', 'Trooper Profile')

@section('content')

    <x-slim-container>

        <x-message type="info"
                   icon="fa-brands fa-empire"
                   class="w-100">
            Welcome to Trooper Tracker 2.0! To get started, please complete some necessary setup provided below.
        </x-message>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <p class="text-warning">
                    Is your email correct?
                </p>

                <x-input-container>
                    <x-label>
                        Email:
                    </x-label>
                    <x-input-text :property="'email'"
                                  :value="$trooper->email" />
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
                        Notification Frequency:
                    </x-label>
                    <x-input-select :property="'notification_frequency'"
                                    :options="\App\Enums\NotificationFrequency::toArray()"
                                    :value="$trooper->notification_frequency->value" />
                    <x-input-help>
                        How often would you like to receive notification emails about events, milestones, and other important updates?
                    </x-input-help>
                </x-input-container>

                <x-transmission-bar :id="'organizations'" />

                <p class="text-warning">
                    Are your assigned organizations correct, whether you're a member or a handler?
                </p>

                <x-table>
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th>Member Of</th>
                        </tr>
                    </thead>
                    @foreach ($organization_memberships as $organization)
                        <tr>
                            <td>
                                {{ $organization->name }}
                            </td>
                            <td>
                                <x-input-picker :property="'organizations.' . $organization->id . '.assignment'"
                                                :route="'pickers.organization'"
                                                :params="['organization_id' => $organization->id]"
                                                :text="$organization->assignment->name ?? '-- Not a Member --'"
                                                :value="$organization->assignment->id ?? null" />
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <x-submit-container>
                    <x-submit-button>
                        Update
                    </x-submit-button>
                </x-submit-container>
            </form>

        </x-card>

    </x-slim-container>
    
    <x-modal-picker :label="'Select an Organization for Membership'" />

@endsection
