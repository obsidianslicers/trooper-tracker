@extends('layouts.base')

@section('page-title', 'Command Staff')

@section('content')

    <x-dashboard-cards x-data="Admin.cardNavigator()"
                       x-on:click="navigate">
        @if($not_approved)
            <x-dashboard-card :label="'Trooper Approvals'"
                              :icon="'fa-user-check'"
                              :url="route('admin.troopers.approvals')">
                <p class="text-warning">
                    {{ $not_approved }} awaiting approval
                </p>
            </x-dashboard-card>
        @endif
        @if($pending_join_requests)
            <x-dashboard-card :label="'Club Join Requests'"
                              :icon="'fa-user-plus'"
                              :url="route('admin.troopers.approvals')">
                <p class="text-warning">
                    {{ $pending_join_requests }} {{ $pending_join_requests === 1 ? 'request' : 'requests' }} pending
                </p>
            </x-dashboard-card>
        @endif
        <x-dashboard-card :label="'Troopers'"
                          :icon="'fa-users-gear'"
                          :url="route('admin.troopers.list')">
            Manage Trooper's profile, authority, and memberships.
        </x-dashboard-card>
        <x-dashboard-card :label="'Notices to the Troops'"
                          :icon="'fa-message'"
                          :url="route('admin.notices.list')">
            <p>
                Manage Site Messages
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Events'"
                          :icon="'fa-calendar-days'"
                          :url="route('admin.events.list')">
            <p>
                Create, Update, and Manage Events
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Awards'"
                          :icon="'fa-award'"
                          :url="route('admin.awards.list')">
            <p>
                Create, Update Awards, as well as assign them to troopers
            </p>
        </x-dashboard-card>
        @can('create', \App\Models\Costume::class)
            <x-dashboard-card :label="'Costumes'"
                              :icon="'fa-wrench'"
                              :url="route('admin.costumes.list')">
                <p>
                    Create, Update, and Manage Costumes
                </p>
            </x-dashboard-card>
        @endcan
        <x-dashboard-card :label="'Organizations'"
                          :icon="'fa-wrench'"
                          :url="route('admin.organizations.list')">
            <p>
                Create, Update, and Manage Organizations, Regions, Units
            </p>
        </x-dashboard-card>
        <x-dashboard-card :label="'Reports & Stats'"
                          :icon="'fa-file-lines'"
                          :url="route('admin.reports.display')">
            <p>
                View Reports &amp; Statistics
            </p>
        </x-dashboard-card>
    </x-dashboard-cards>

@endsection