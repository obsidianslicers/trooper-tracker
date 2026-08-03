@extends('layouts.base')

@section('page-title', 'Command Staff')

@section('content')

<div x-data="{
                    notApproved: {{ $not_approved }},
                    pendingJoinRequests: {{ $pending_join_requests }},
                    dismissedApprovalCount: $persist(0).as('admin_dismissed_approvals_{{ auth()->id() }}'),
                    dismissedRequestCount: $persist(0).as('admin_dismissed_requests_{{ auth()->id() }}'),
                    showApproval: false,
                    showRequest: false,
                    init() {
                        this.showApproval = this.notApproved > this.dismissedApprovalCount;
                        this.showRequest = this.pendingJoinRequests > this.dismissedRequestCount;
                    },
                    dismissApproval() {
                        this.dismissedApprovalCount = this.notApproved;
                        this.showApproval = false;
                    },
                    dismissRequest() {
                        this.dismissedRequestCount = this.pendingJoinRequests;
                        this.showRequest = false;
                    }
                }">
    <div x-show="showApproval"
         x-transition
         class="alert alert-warning alert-dismissible mt-2">
        <strong>
            <i class="fa fa-fw fa-solid fa-circle-exclamation"></i>
            @if($not_approved === 1)
                There is 1 trooper ready for action!
            @else
                There are {{ $not_approved }} troopers ready for action!
            @endif
        </strong>
        <button type="button"
                class="btn-close"
                @click="dismissApproval()"></button>
    </div>
    <div x-show="showRequest"
         x-transition
         class="alert alert-warning alert-dismissible mt-2">
        <strong>
            <i class="fa fa-fw fa-solid fa-circle-exclamation"></i>
            @if($pending_join_requests === 1)
                1 trooper has a pending request!
            @else
                {{ $pending_join_requests }} troopers have a pending request!
            @endif
        </strong>
        <button type="button"
                class="btn-close"
                @click="dismissRequest()"></button>
    </div>
</div>

<x-dashboard-cards x-data="Admin.cardNavigator()"
                   x-on:click="navigate">
    <x-dashboard-card :label="'Trooper Approvals'"
                      :icon="'fa-user-check'"
                      :url="route('admin.troopers.approvals')">
        @if($not_approved > 0)
            <p class="text-warning">
                {{ $not_approved }} awaiting approval
            </p>
        @else
            <p class="text-success">All troopers approved</p>
        @endif

        @if($pending_join_requests > 0)
            <p class="text-warning mb-0">
                {{ $pending_join_requests }} pending request{{ $pending_join_requests === 1 ? '' : 's' }}
            </p>
        @else
            <p class="text-success mb-0">No pending requests</p>
        @endif
    </x-dashboard-card>
    <x-dashboard-card :label="'Troopers'"
                      :icon="'fa-users-gear'"
                      :url="route('admin.troopers.list')">
        Manage Trooper's profile, authority, and memberships.
    </x-dashboard-card>
    @role(['administrator'])
    <x-dashboard-card :label="'Merge Troopers'"
                      :icon="'fa-user-group'"
                      :url="route('admin.troopers.merge')">
        Merge existing troopers into a single profile.
    </x-dashboard-card>
    @endrole
    <x-dashboard-card :label="'Notices to the Troops'"
                      :icon="'fa-message'"
                      :url="route('admin.notices.list')">
        Manage Site Messages
    </x-dashboard-card>
    <x-dashboard-card :label="'Events'"
                      :icon="'fa-calendar-days'"
                      :url="route('admin.events.list')">
        Create, Update, and Manage Events
    </x-dashboard-card>
    <x-dashboard-card :label="'Awards'"
                      :icon="'fa-award'"
                      :url="route('admin.awards.list')">
        Create, Update Awards, as well as assign them to troopers
    </x-dashboard-card>
    @can('create', \App\Models\Costume::class)
        <x-dashboard-card :label="'Costumes'"
                          :icon="'fa-wrench'"
                          :url="route('admin.costumes.list')">
            Create, Update, and Manage Costumes
        </x-dashboard-card>
    @endcan
    <x-dashboard-card :label="'Organizations'"
                      :icon="'fa-wrench'"
                      :url="route('admin.organizations.list')">
        Create, Update, and Manage Organizations, Regions, Units
    </x-dashboard-card>
    <x-dashboard-card :label="'Reports & Stats'"
                      :icon="'fa-file-lines'"
                      :url="route('admin.reports.display')">
        View Reports &amp; Statistics
    </x-dashboard-card>
    @role(['administrator'])
    <x-dashboard-card :label="'System Check'"
                      :icon="'fa-heart-pulse'"
                      :url="route('admin.system-check')">
        Verify PHP, server, and integration settings
    </x-dashboard-card>
    @endrole
</x-dashboard-cards>

@endsection