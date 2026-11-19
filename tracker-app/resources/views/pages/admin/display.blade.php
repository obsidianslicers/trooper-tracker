@extends('layouts.base')

@section('page-title', __('admin.Command Staff'))

@section('content')

<div x-data="{
                    notApproved: {{ $not_approved }},
                    pendingJoinRequests: {{ $pending_join_requests }},
                    pendingPermissionRequests: {{ $pending_permission_requests }},
                    dismissedApprovalCount: $persist(0).as('admin_dismissed_approvals_{{ auth()->id() }}'),
                    dismissedRequestCount: $persist(0).as('admin_dismissed_requests_{{ auth()->id() }}'),
                    dismissedPermissionCount: $persist(0).as('admin_dismissed_permission_requests_{{ auth()->id() }}'),
                    showApproval: false,
                    showRequest: false,
                    showPermission: false,
                    init() {
                        this.showApproval = this.notApproved > this.dismissedApprovalCount;
                        this.showRequest = this.pendingJoinRequests > this.dismissedRequestCount;
                        this.showPermission = this.pendingPermissionRequests > this.dismissedPermissionCount;
                    },
                    dismissApproval() {
                        this.dismissedApprovalCount = this.notApproved;
                        this.showApproval = false;
                    },
                    dismissRequest() {
                        this.dismissedRequestCount = this.pendingJoinRequests;
                        this.showRequest = false;
                    },
                    dismissPermission() {
                        this.dismissedPermissionCount = this.pendingPermissionRequests;
                        this.showPermission = false;
                    }
                }">
    <div x-show="showApproval"
         x-transition
         class="alert alert-warning alert-dismissible mt-2">
        <strong>
            <i class="fa fa-fw fa-solid fa-circle-exclamation"></i>
            @if($not_approved === 1)
                {{ __('admin.There is 1 trooper ready for action!') }}
            @else
                {{ __('admin.There are :count troopers ready for action!', ['count' => $not_approved]) }}
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
                {{ __('admin.1 trooper has a pending request!') }}
            @else
                {{ __('admin.:count troopers have a pending request!', ['count' => $pending_join_requests]) }}
            @endif
        </strong>
        <button type="button"
                class="btn-close"
                @click="dismissRequest()"></button>
    </div>
    @if($pending_permission_requests > 0)
    <div x-show="showPermission"
         x-transition
         class="alert alert-warning alert-dismissible mt-2">
        <strong>
            <i class="fa fa-fw fa-solid fa-circle-exclamation"></i>
            <a href="{{ route('admin.troopers.permissions.approvals') }}" class="alert-link">
                @if($pending_permission_requests === 1)
                    {{ __('admin.1 permission request is waiting for review!') }}
                @else
                    {{ __('admin.:count permission requests are waiting for review!', ['count' => $pending_permission_requests]) }}
                @endif
            </a>
        </strong>
        <button type="button"
                class="btn-close"
                @click="dismissPermission()"></button>
    </div>
    @endif
</div>

<x-dashboard-cards x-data="Admin.cardNavigator()"
                   x-on:click="navigate">
    <x-dashboard-card :label="__('admin.Trooper Approvals')"
                      :icon="'fa-user-check'"
                      :url="route('admin.troopers.approvals.index')">
        @if($not_approved > 0)
            <p class="text-warning">
                {{ __('admin.:count awaiting approval', ['count' => $not_approved]) }}
            </p>
        @else
            <p class="text-success">{{ __('admin.All troopers approved') }}</p>
        @endif

        @if($pending_join_requests > 0)
            <p class="text-warning mb-0">
                {{ trans_choice('admin.:count pending request|:count pending requests', $pending_join_requests) }}
            </p>
        @else
            <p class="text-success mb-0">{{ __('admin.No pending requests') }}</p>
        @endif
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Costume Approvals')"
                      :icon="'fa-user-astronaut'"
                      :url="route('admin.troopers.costume-approvals')">
        @if($pending_costumes > 0)
            <p class="text-warning mb-0">
                {{ trans_choice('admin.:count costume awaiting review|:count costumes awaiting review', $pending_costumes) }}
            </p>
        @else
            <p class="text-success mb-0">{{ __('admin.No costumes waiting') }}</p>
        @endif
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Troop Verifications')"
                      :icon="'fa-user-clock'"
                      :url="route('admin.troopers.troop-verifications')">
        @if($troops_awaiting_verification > 0)
            <p class="text-warning mb-0">
                {{ trans_choice('admin.:count troop awaiting verification|:count troops awaiting verification', $troops_awaiting_verification) }}
            </p>
        @else
            <p class="text-success mb-0">{{ __('admin.No troops waiting') }}</p>
        @endif
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Troopers')"
                      :icon="'fa-users-gear'"
                      :url="route('admin.troopers.list')">
        {{ __('admin.Manage Trooper\'s profile, authority, and memberships.') }}
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Missing Credit')"
                      :icon="'fa-triangle-exclamation'"
                      :url="route('admin.service-records.missing-credits')">
        {{ __('admin.Fix shifts missing a "Credited To" club') }}
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Request Permissions')"
                      :icon="'fa-user-shield'"
                      :url="route('admin.troopers.permissions.request')">
        {{ __('admin.Ask an administrator to change a trooper\'s role or forum groups.') }}
    </x-dashboard-card>
    @role(['system_owner', 'staff_officer'])
    <x-dashboard-card :label="__('admin.Permission Approvals')"
                      :icon="'fa-user-lock'"
                      :url="route('admin.troopers.permissions.approvals')">
        @if($pending_permission_requests > 0)
            <p class="text-warning mb-0">
                {{ trans_choice('admin.:count pending request|:count pending requests', $pending_permission_requests) }}
            </p>
        @else
            <p class="text-success mb-0">{{ __('admin.No pending requests') }}</p>
        @endif
    </x-dashboard-card>
    @endrole
    @role(['system_owner'])
    <x-dashboard-card :label="__('admin.Merge Troopers')"
                      :icon="'fa-user-group'"
                      :url="route('admin.troopers.merge')">
        {{ __('admin.Merge existing troopers into a single profile.') }}
    </x-dashboard-card>
    @endrole
    <x-dashboard-card :label="__('admin.Notices to the Troops')"
                      :icon="'fa-message'"
                      :url="route('admin.notices.list')">
        {{ __('admin.Manage Site Messages') }}
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Events')"
                      :icon="'fa-calendar-days'"
                      :url="route('admin.events.list')">
        {{ __('admin.Create, Update, and Manage Events') }}
    </x-dashboard-card>
    @can('viewAny', \App\Models\EventType::class)
        <x-dashboard-card :label="__('admin.Event Types')"
                          :icon="'fa-tags'"
                          :url="route('admin.event-types.index')">
            {{ __('admin.Order event types, describe them, and choose which are non-troop-credit') }}
        </x-dashboard-card>
    @endcan
    <x-dashboard-card :label="__('admin.Awards')"
                      :icon="'fa-award'"
                      :url="route('admin.awards.list')">
        {{ __('admin.Create, Update Awards, as well as assign them to troopers') }}
    </x-dashboard-card>
    @can('create', \App\Models\Costume::class)
        <x-dashboard-card :label="__('admin.Costumes')"
                          :icon="'fa-wrench'"
                          :url="route('admin.costumes.list')">
            {{ __('admin.Create, Update, and Manage Costumes') }}
        </x-dashboard-card>
    @endcan
    @role(['system_owner'])
    <x-dashboard-card :label="__('admin.Merge Costumes')"
                      :icon="'fa-object-group'"
                      :url="route('admin.costumes.merge')">
        {{ __('admin.Merge a duplicate costume into the one you want to keep.') }}
    </x-dashboard-card>
    @endrole
    <x-dashboard-card :label="__('admin.Organizations')"
                      :icon="'fa-wrench'"
                      :url="route('admin.organizations.list')">
        {{ __('admin.Create, Update, and Manage Organizations, Regions, Units') }}
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Reports & Stats')"
                      :icon="'fa-file-lines'"
                      :url="route('admin.reports.display')">
        {{ __('admin.View Reports & Statistics') }}
    </x-dashboard-card>
    @role(['system_owner', 'staff_officer'])
    <x-dashboard-card :label="__('admin.FAQ & Help')"
                      :icon="'fa-question'"
                      :url="route('admin.faq.index')">
        {{ __('admin.View Frequently Asked Questions and Help') }}
    </x-dashboard-card>
    @endrole
    @role(['system_owner'])
    <x-dashboard-card :label="__('admin.System Check')"
                      :icon="'fa-heart-pulse'"
                      :url="route('admin.system-check')"
                      :class="$system_check_problems->isNotEmpty() ? 'border-danger' : ''">
        {{ __('admin.Verify PHP, server, and integration settings') }}
        @if($system_check_problems->isNotEmpty())
        <x-slot:after>
            <p class="card-text text-danger fw-bold mb-1">
                {{ trans_choice('admin.:count check needs attention|:count checks need attention', $system_check_problems->count()) }}
            </p>
            <ul class="list-unstyled small mb-0">
                @foreach($system_check_problems as $problem)
                <li class="mb-1">
                    <span class="badge {{ $problem->status->badgeClass() }}">
                        <i class="fa fa-fw fa-solid {{ $problem->status->icon() }}"></i>
                    </span>
                    {{ $problem->label }}
                    @if($problem->detail)
                    <br><span class="text-muted">{{ $problem->detail }}</span>
                    @endif
                </li>
                @endforeach
            </ul>
        </x-slot:after>
        @endif
    </x-dashboard-card>
    <x-dashboard-card :label="__('admin.Plugins')"
                      :icon="'fa-puzzle-piece'"
                      :url="route('admin.plugins.list')">
        {{ __('admin.Enable, Disable, and Configure Plugins') }}
    </x-dashboard-card>
    @endrole
    @pluginSlot('admin.dashboard.cards')
</x-dashboard-cards>

@endsection