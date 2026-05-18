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
                        Theme:
                    </x-label>
                    <x-input-select :property="'theme'"
                                    :options="\App\Enums\TrooperTheme::toArray()"
                                    :value="$trooper->theme->value" />
                    <x-input-help>
                        Choose your preferred theme for the application interface.
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

                @if($organization_memberships->whereNotNull('assignment')->isNotEmpty())
                    <x-transmission-bar :id="'organizations'" />

                    <p class="text-warning">
                        Your current organization memberships:
                    </p>

                    <ul class="list-group mb-3">
                        @foreach($organization_memberships->whereNotNull('assignment') as $organization)
                            <li class="list-group-item">
                                <strong>{{ $organization->name }}</strong> — {{ $organization->assignment->name }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                <x-submit-container>
                    <x-submit-button>
                        Update
                    </x-submit-button>
                </x-submit-container>
            </form>

        </x-card>

    </x-slim-container>
    

@endsection
