@extends('layouts.base')

@section('page-title', 'Trooper Notifications')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>

        <x-card>

            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Notification Frequency:
                    </x-label>
                    <x-input-select :property="'notification_frequency'"
                                    :options="\App\Enums\NotificationFrequency::toArray()"
                                    :value="$notification_frequency->value" />
                    <x-input-help>
                        How often would you like to receive notification emails about events, milestones, and other important updates?
                    </x-input-help>
                </x-input-container>

                <h3>Squads / Clubs</h3>
                <p>
                    <i>Note: Events are categorized by 501st region territory. To receive event notifications for a particular area,
                        ensure you subscribed to the appropriate region(s). Organization notifications are used in command staff e-mails, to send
                        command staff information on trooper milestones based on region or organization.</i>
                </p>

                <div x-data="Account.Profile.notificationSelector()">

                    @foreach ($organizations as $organization)
                        <x-input-container class="ps-5">
                            <x-input-checkbox :property="'organizations.' . $organization->id . '.should_notify'"
                                              :label="$organization->name"
                                              :value="1"
                                              :checked="$organization->selected"
                                              data-organization-id="{{ $organization->id }}"
                                              x-on:change="toggleOrganization({{ $organization->id }}, $event.target.checked)" />
                            @foreach ($organization->organizations as $region)
                                <x-input-container class="ps-5">
                                    <x-input-checkbox :property="'organizations.' . $region->id . '.should_notify'"
                                                      :label="$region->name"
                                                      :value="1"
                                                      :checked="$region->selected"
                                                      data-organization-id="{{ $organization->id }}"
                                                      data-region-id="{{ $region->id }}"
                                                      x-on:change="toggleRegion({{ $region->id }}, $event.target.checked)" />
                                    @foreach ($region->organizations as $unit)
                                        <x-input-container class="ps-5">
                                            <x-input-checkbox :property="'organizations.' . $unit->id . '.should_notify'"
                                                              :label="$unit->name"
                                                              :value="1"
                                                              :checked="$unit->selected"
                                                              data-organization-id="{{ $organization->id }}"
                                                              data-region-id="{{ $region->id }}" />
                                        </x-input-container>
                                    @endforeach
                                </x-input-container>
                            @endforeach
                        </x-input-container>
                    @endforeach

                </div>

                <x-submit-container>
                    <x-submit-button>
                        Update
                    </x-submit-button>
                </x-submit-container>
            </form>

        </x-card>

    </x-slim-container>

@endsection