@extends('layouts.base')

@section('page-title', 'Reports Dashboard')

@section('content')

    <x-message>
        Report data has been filtered to align with your current moderation privileges.
        Should your clearance ever improve, additional results may become available.
        Until then, thank you for your patience.
        We admire your initiative, even if the Empire does not.
    </x-message>

    <x-dashboard-cards x-data="Admin.cardNavigator()"
                       x-on:click="navigate">
        <x-dashboard-card :label="'Event Summary'"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.event-summary')">
            <p>
                Event Summary (Total count of troopers at all events)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Trooper Event Summary'"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.trooper-event-summary')">
            <p>
                Trooper Event Summary
                (Report to get amount of events attended by each trooper. You can do all or search by club)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Troopers Without Activity'"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.troopers-without-activity')">
            <p>
                Active Troopers who have not {{ \App\Enums\EventTrooperStatus::ATTENDED->name }}
                an event in the last 12 months.
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Trooper Status Change Log'"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.status-change-log')">
            <p>
                Trooper Status Change Log - Those troopers who {{ \App\Enums\EventTrooperStatus::ATTENDED->name }}
                an event where their status was updated by someone other than the trooper in action.
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Costume Used Most at Events'"
                          :icon="'fa-chart-line'"
                          :url="'#'">
            <p>
                <b class="text-danger">TODO:</b> Costume used most at Events
                (Report to see which costume was used the most at events between certain dates)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Event Types Counts'"
                          :icon="'fa-chart-line'"
                          :url="route('admin.reports.event-type-count')">
            <p>
                Counts of event-types
            </p>
        </x-dashboard-card>
    </x-dashboard-cards>

@endsection