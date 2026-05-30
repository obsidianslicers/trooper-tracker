@extends('layouts.base')

@section('page-title', 'Trooper Notifications')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>

        <x-card>

            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <h3>Notification Channels</h3>

                <x-input-container>
                    <x-input-checkbox :property="'push_notifications_enabled'"
                                      :label="'Mobile Push Notifications'"
                                      :value="1"
                                      :checked="$push_notifications_enabled" />
                    <x-input-help>
                        Receive push alerts on your registered mobile device. This does not affect email notifications.
                    </x-input-help>
                </x-input-container>

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

                <h3>Notification Preferences</h3>
                <p>
                    <i>Control which channels you receive notifications on. Website notifications appear in your notification inbox.</i>
                </p>

                <div class="row fw-bold mb-2 d-none d-md-flex">
                    <div class="col-md-5"></div>
                    <div class="col-4 col-md-2 text-center">Email</div>
                    <div class="col-4 col-md-2 text-center">Push</div>
                    <div class="col-4 col-md-2 text-center">Website</div>
                </div>

                @foreach ([
                    'event_created'   => 'New Events',
                    'event_cancelled' => 'Event Cancellations',
                    'trooper_signed_up' => 'Sign-up Confirmations',
                    'event_shift_completed' => 'Event Shift Completed',
                ] as $category => $label)
                    <div class="row align-items-center mb-3">
                        <div class="col-12 col-md-5 mb-1 mb-md-0">{{ $label }}</div>
                        @foreach (['mail' => 'Email', 'fcm' => 'Push', 'database' => 'Website'] as $channel => $channel_label)
                            <div class="col-4 col-md-2 text-center">
                                <span class="d-inline d-md-none small text-muted me-1">{{ $channel_label }}</span>
                                <input type="hidden"
                                       name="notification_preferences[{{ $category }}][{{ $channel }}]"
                                       value="0">
                                <input type="checkbox"
                                       name="notification_preferences[{{ $category }}][{{ $channel }}]"
                                       value="1"
                                       @checked($notification_preferences[$category][$channel] ?? true)>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @if (auth()->user()->is_administrator || auth()->user()->is_moderator)

                    <h3>Administrative Notifications</h3>
                    <p>
                        <i>Control which channels you receive administrative notifications on.</i>
                    </p>

                    @foreach ([
                        'join_requests'         => 'Club Join Requests',
                        'trooper_registrations' => 'New Trooper Registrations',
                    ] as $category => $label)
                        <div class="row align-items-center mb-3">
                            <div class="col-12 col-md-5 mb-1 mb-md-0">{{ $label }}</div>
                            @foreach (['mail' => 'Email', 'fcm' => 'Push', 'database' => 'Website'] as $channel => $channel_label)
                                <div class="col-4 col-md-2 text-center">
                                    <span class="d-inline d-md-none small text-muted me-1">{{ $channel_label }}</span>
                                    <input type="hidden"
                                           name="notification_preferences[{{ $category }}][{{ $channel }}]"
                                           value="0">
                                    <input type="checkbox"
                                           name="notification_preferences[{{ $category }}][{{ $channel }}]"
                                           value="1"
                                           @checked($notification_preferences[$category][$channel] ?? true)>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                @endif

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