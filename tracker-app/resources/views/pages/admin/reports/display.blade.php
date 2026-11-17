@extends('layouts.base')

@section('page-title', __('admin.Reports Dashboard'))

@section('content')

    @include('pages.admin.reports.inc.dashboard')

    <x-message>
        {{ __('admin.Report data below will be filtered to align with your current moderation privileges. Should your clearance ever improve, additional results may become available. Until then, thank you for your patience. We admire your initiative, even if the Empire does not.') }}
    </x-message>

    <x-dashboard-cards x-data="Admin.cardNavigator()"
                       x-on:click="navigate">
        <x-dashboard-card :label="__('admin.Event Summary')"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.event-summary')">
            <p>
                {{ __('admin.Event Summary (Total count of troopers at all events)') }}
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="__('admin.Trooper Event Summary')"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.trooper-event-summary')">
            <p>
                {{ __('admin.Trooper Event Summary (Report to get amount of events attended by each trooper. You can do all or search by club)') }}
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="__('admin.Troopers Without Activity')"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.troopers-without-activity')">
            <p>
                {{ __('admin.Active Troopers who have not :attended an event in the last 12 months.', ['attended' => \App\Enums\EventTrooperStatus::ATTENDED->label()]) }}
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="__('admin.Trooper Status Change Log')"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.status-change-log')">
            <p>
                {{ __('admin.Trooper Status Change Log - Those troopers who :attended an event where their status was updated by someone other than the trooper in action.', ['attended' => \App\Enums\EventTrooperStatus::ATTENDED->label()]) }}
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="__('admin.Donation Event Summary')"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.donation-event-summary')">
            <p>
                {{ __('admin.Donation Event Summary (Report showing charity direct/indirect funds raised per event with date range filtering)') }}
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="__('admin.Costume Used Most at Events')"
                          :icon="'fa-chart-line'"
                          :url="route('admin.reports.costume-event-summary')">
            <p>
                {{ __('admin.Costume used most at Events (Report to see which costume was used the most at events between certain dates)') }}
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="__('admin.Event Types Counts')"
                          :icon="'fa-chart-line'"
                          :url="route('admin.reports.event-type-count')">
            <p>
                {{ __('admin.Counts of event-types') }}
            </p>
        </x-dashboard-card>
    </x-dashboard-cards>

@endsection