@extends('layouts.base')

@section('page-title', 'Reports Dashboard')

@section('content')

    <x-dashboard-cards x-data="Admin.cardNavigator()"
                       x-on:click="navigate">
        <x-dashboard-card :label="'Search Events'"
                          :icon="'fa-file-lines'"
                          :url="'#'">
            <p>
                TODO: Search all events by name, trooper attended, dates, TKID
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Event Counts per Trooper'"
                          :icon="'fa-file-lines'"
                          :url="'#'">
            <p>
                TODO: Troop Count per Trooper (Report to get amount of events attended by each trooper. You can do all or search by club)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Donation Counts per Event'"
                          :icon="'fa-file-lines'"
                          :url="'#'">
            <p>
                TODO: Donation Count per Event (Donation Stats by date, can sort by charity event only and/or events with data only) this can be done as
                total or by club/squad
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Active Troopers w/o a Troop'"
                          :icon="'fa-file-lines'"
                          :url="'#'">
            <p>
                TODO: Active Troopers without a Troop (Report to identify active troopers who are not assigned to any troop)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Troopers Change Log'"
                          :icon="'fa-file-lines'"
                          :url="'#'">
            <p>
                TODO: Troopers Change Log (Report to track changes made to trooper records over time)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Costume Troop Counts Between Dates'"
                          :icon="'fa-file-lines'"
                          :url="'#'">
            <p>
                TODO: Costume troop count between dates (Can see amount of events done in a costume between certain dates)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Volunteers at Events'"
                          :icon="'fa-file-lines'"
                          :url="'#'">
            <p>
                TODO: Volunteers at Events (Total count of troopers at all events)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Costume Used Most at Events'"
                          :icon="'fa-chart-line'"
                          :url="'#'">
            <p>
                TODO: Costume used most at Events (Report to see which costume was used the most at events between certain dates)
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Donations Raised'"
                          :icon="'fa-chart-line'"
                          :url="'#'">
            <p>
                TODO: Direct Doantions Raised &amp; Indirect Donations Raised
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Event Types Counts'"
                          :icon="'fa-chart-line'"
                          :url="'#'">
            <p>
                TODO: Counts of event-types
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Event Categories Counts'"
                          :icon="'fa-chart-line'"
                          :url="'#'">
            <p>
                TODO: Total events in Tracker
            </p>
        </x-dashboard-card>
    </x-dashboard-cards>

@endsection