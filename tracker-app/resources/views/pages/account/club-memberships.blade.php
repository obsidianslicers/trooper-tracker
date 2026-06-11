@extends('layouts.base')

@section('page-title', 'Club Memberships')

@section('content')

@include('pages.account.tabs')

<x-transmission-bar :id="'club-memberships'" />

<x-slim-container>

    @if($pending_requests->isNotEmpty())
        <x-card>
            <h6 class="mb-3">
                <i class="fa fa-fw fa-hourglass-end text-warning"></i> Pending Membership Requests
            </h6>
            <ul class="list-group list-group-flush">
                @foreach($pending_requests as $request)
                    @php
                        $org = $request->organization;
                        $path_ids = collect(array_filter(explode(':', trim($org->node_path, ':'))));
                        $path_names = $path_ids->map(fn($id) => $ancestors[$id]?->name ?? '?');
                        $root_id = (int) $path_ids->first();
                        $root_org = $ancestors[$root_id] ?? null;
                    @endphp
                    <li class="list-group-item d-flex align-items-center justify-content-between gap-2 px-0">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fa fa-fw fa-clock text-warning"></i>
                            <x-logo :storage_path="$root_org?->image_path_sm" default_path="img/icons/organization-128x128.png" width="24" height="24" />
                            {{ $path_names->implode(' — ') }}
                        </span>
                        <small class="text-muted">
                            {{ $request->updated_at->diffForHumans() }}
                        </small>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @if($denied_requests->isNotEmpty())
        <x-card>
            <h6 class="mb-3">
                <i class="fa fa-fw fa-circle-xmark text-danger"></i> Recently Denied Requests
            </h6>
            <ul class="list-group list-group-flush">
                @foreach($denied_requests as $request)
                    @php
                        $org = $request->organization;
                        $path_ids = array_filter(explode(':', trim($org->node_path, ':')));
                        $path_names = collect($path_ids)->map(fn($id) => $ancestors[$id]?->name ?? '?');
                    @endphp
                    <li class="list-group-item px-0">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span>
                                <i class="fa fa-fw fa-circle-xmark text-danger"></i>
                                {{ $path_names->implode(' — ') }}
                            </span>
                            <small class="text-muted">{{ $request->updated_at->diffForHumans() }}</small>
                        </div>
                        @if($request->denial_reason)
                            <small class="text-muted d-block mt-1">{{ $request->denial_reason }}</small>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @if($current_clubs->isNotEmpty())
        <x-card>
            <h6 class="mb-3">Current Club Assignments</h6>
            <ul class="list-group list-group-flush">
                @foreach($current_clubs as $club)
                    @php
                        $path_ids = collect(array_filter(explode(':', trim($club->node_path, ':'))));
                        $path_names = $path_ids->map(fn($id) => $ancestors[$id]?->name ?? '?');
                        $root_id = (int) $path_ids->first();
                        $root_org = $ancestors[$root_id] ?? null;
                    @endphp
                    <li class="list-group-item d-flex align-items-center gap-2 px-0">
                        @if($club->is_pending)
                            <i class="fa fa-fw fa-clock text-warning"></i>
                        @else
                            <i class="fa fa-fw fa-circle-check text-success"></i>
                        @endif
                        <x-logo :storage_path="$root_org?->image_path_sm" default_path="img/icons/organization-128x128.png" width="24" height="24" />
                        <span>{{ $path_names->implode(' — ') }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <div id="club-membership-form">
        @if($available_clubs->isEmpty())
            <x-message type="info"
                       icon="fa-solid fa-circle-info">
                You are already a member of all available clubs, or no clubs are available to join at this time.
            </x-message>
        @else
        @php
            $grouped = $available_clubs->groupBy(fn($org) => explode(\App\Models\Organization::NODE_PATH_SEP, $org->node_path)[0]);
            $root_ids = $grouped->keys()->map(fn($id) => (int) $id)->toArray();
            $root_names = \App\Models\Organization::whereIn(\App\Models\Organization::ID, $root_ids)
                ->pluck(\App\Models\Organization::NAME, \App\Models\Organization::ID);
        @endphp

        <x-card>
            <div x-data="{
                        orgs: {{ Js::from($available_clubs_data) }},
                        orgMap: {{ Js::from($ancestor_map_data) }},
                        selectedId: @js((string) old('organization_id', '')),
                        get selectedOrg() {
                            const id = parseInt(this.selectedId);
                            return this.orgs.find(o => o.id === id) ?? null;
                        },
                        get selectedChain() {
                            if (!this.selectedOrg?.node_path) return [];
                            return this.selectedOrg.node_path
                                .split(':')
                                .filter(id => id !== '')
                                .map(id => this.orgMap[parseInt(id)])
                                .filter(Boolean);
                        },
                        get isIdentifierRequired() {
                            if (!this.selectedOrg?.identifier_validation) return false;
                            return this.selectedOrg.identifier_validation.split('|').includes('required');
                        }
                    }">
                        <p class="text-muted mb-3">
                            Select a club below only if you are already a member of that organization. A moderator will review your request and approve access.
                            @if(!$trooper->is_visitor)
                                Choose the most specific unit that applies to you — access is granted through the full hierarchy.
                            @endif
                            Access is not required to view events from the organization. Only one access membership per organization is allowed.
                        </p>

                <x-input-container>
                    <x-label>Club / Organization:</x-label>
                    <select name="organization_id"
                            id="organization_id"
                            x-model="selectedId"
                            class="form-select @error('organization_id') is-invalid @enderror">
                        <option value="">— Select an organization —</option>
                        @foreach($grouped as $root_id => $orgs)
                        @php($root_org = $orgs->firstWhere('depth', 0))
                        @php($child_orgs = $orgs->where('depth', '>', 0)->values())
                        @if($root_org)
                            <option value="{{ $root_org->id }}">{{ $root_org->name }}</option>
                        @endif
                        @if($child_orgs->isNotEmpty())
                            <optgroup label="{{ $root_names[$root_id] ?? 'Other' }}">
                                @foreach($child_orgs as $org)
                                    <option value="{{ $org->id }}">{{ str_repeat('— ', $org->depth) }}{{ $org->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @endforeach
                    </select>
                    <x-input-error :property="'organization_id'" />
                </x-input-container>

                <div x-show="selectedId && selectedChain.length > 1"
                     x-cloak
                     class="mt-3 mb-3 p-3 bg-body-tertiary rounded border">
                    <p class="text-muted small mb-0">
                        <i class="fa fa-fw fa-circle-info"></i>
                        Requesting access to all levels:
                    </p>
                    <div class="mt-3">
                    <template x-for="(item, index) in selectedChain" :key="index">
                        <div class="d-flex align-items-center gap-2 py-1"
                             :style="`padding-left: calc(${index} * 1.25rem)`">
                            <img :src="item.image_url" alt="" width="20" height="20" style="object-fit: contain; flex-shrink: 0;" />
                            <span x-text="item.name" class="small"></span>
                            <span x-show="index === selectedChain.length - 1"
                                  class="badge bg-primary-subtle text-primary-emphasis ms-1 small">
                                your selection
                            </span>
                        </div>
                    </template>
                    </div>
                </div>

                <div x-show="selectedId"
                     x-cloak>
                    <x-input-container>
                        <x-label><span x-text="selectedOrg?.identifier_display ?? 'Member ID'">Member ID</span><span class="text-muted"
                                  x-show="!isIdentifierRequired"> (optional)</span>:</x-label>
                        <input type="text"
                               name="identifier"
                               id="identifier"
                               class="form-control @error('identifier') is-invalid @enderror"
                               :placeholder="selectedOrg?.identifier_display ?? 'Member ID'"
                               :required="isIdentifierRequired"
                               maxlength="64"
                               value="{{ old('identifier') }}" />
                        <x-input-help>
                            Enter your member ID for this organization if you have one. Leave blank if unknown.
                        </x-input-help>
                        <x-input-error :property="'identifier'" />
                    </x-input-container>
                </div>

                <div class="row mt-3">
                    <div class="col-12 d-flex flex-column flex-md-row justify-content-md-end align-items-md-center gap-2">
                        <button type="button"
                                class="btn btn-primary"
                                hx-post="{{ route('account.club-memberships-htmx') }}"
                                hx-include="#organization_id, #identifier"
                                hx-target="#club-membership-form"
                                hx-select="#club-membership-form"
                                hx-swap="outerHTML"
                                hx-indicator="#transmission-bar-club-memberships">
                            <i class="fa fa-fw fa-paper-plane"></i>
                            Request Access
                        </button>
                    </div>
                </div>
            </div>
        </x-card>
        @endif
    </div>
</x-slim-container>

@endsection
