@extends('layouts.base')

@section('page-title', 'Application Status')

@section('content')

<x-transmission-bar :id="'denied'" />


<x-slim-container>

    <x-message type="danger"
               icon="fa-solid fa-circle-xmark"
               class="w-100">
        <strong>Your registration was not approved.</strong>
        @if($denial_reason)
            <div class="mt-2">{{ $denial_reason }}</div>
        @endif
    </x-message>

    @if($denied_requests->isNotEmpty())
        <x-card>
            <h6 class="mb-3">
                <i class="fa fa-fw fa-circle-xmark text-danger"></i> Denied Club Requests
            </h6>
            <p class="text-muted small mb-3">
                The following club requests were part of your original application.
            </p>
            <ul class="list-group list-group-flush">
                @foreach($denied_requests as $request)
                    <li class="list-group-item px-0">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa fa-fw fa-circle-xmark text-danger"></i>
                            <span>{{ $org_paths[$request->organization_id] ?? '—' }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-card>
        <h6 class="mb-3">Resubmit Your Application</h6>

        <p class="text-muted mb-3">
            Update your organization selections below and resubmit for reconsideration.
            A moderator will review your application and you will be notified of the decision.
        </p>

        <form action="{{ route('account.denied.resubmit') }}"
              method="POST"
              novalidate="novalidate"
              x-data="Auth.Register.registration()"
              x-on:organization-toggled="handleOrgToggled($event.detail.id, $event.detail.active)">
            @csrf

            <x-transmission-bar :id="'denied-organization'" />

            @foreach ($organization_hierarchy as $organization)
                <div id="organization-selection-{{ $organization->id }}"
                    x-data="Auth.Register.organizationSelector({ organizationId: {{ $organization->id }} })"
                    x-init="init()">
                    <x-input-container>
                        <x-input-checkbox :property="'organizations.' . $organization->id . '.selected'"
                            :label="$organization->name"
                            :value="'1'"
                            :checked="$organization->selected"
                            x-on:change="toggle" />
                    </x-input-container>

                    <div class="organization-{{ $organization->id }} ps-4"
                        x-transition
                        x-show="active">
                        @if($account_type !== 'handler')
                            <x-input-container>
                                <div class="input-group pointer">
                                    <span class="input-group-text">
                                        {{ $organization->identifier_display }}:
                                    </span>
                                    <x-input-text :property="'organizations.' . $organization->id . '.identifier'"
                                                  :value="$organization->identifier" />
                                </div>
                                <x-input-error :property="'organizations.' . $organization->id . '.identifier'" />
                            </x-input-container>
                        @endif

                        @if(count($organization->regions) > 0)
                            <x-input-container x-show="!isVisitor">
                                <select name="organizations[{{ $organization->id }}][region_id]"
                                    x-model="regionId"
                                    x-on:change="updateUnits"
                                    x-bind:disabled="isVisitor"
                                    class="form-select @error('organizations.' . $organization->id . '.region_id') is-invalid @enderror">
                                    <option value="">-- Select your Region/Garrison --</option>
                                    <template x-for="region in regions" x-bind:key="region.id">
                                        <option x-bind:value="region.id" x-text="region.name"></option>
                                    </template>
                                </select>
                                <x-input-error :property="'organizations.' . $organization->id . '.region_id'" />
                            </x-input-container>

                            <x-input-container x-show="!isVisitor">
                                <select name="organizations[{{ $organization->id }}][unit_id]"
                                        x-model="unitId"
                                        x-bind:disabled="!regionId || isVisitor"
                                        class="form-select @error('organizations.' . $organization->id . '.unit_id') is-invalid @enderror">
                                    <option value="">-- Select your Unit/Squad --</option>
                                    <template x-for="unit in units" x-bind:key="unit.id">
                                        <option x-bind:value="unit.id" x-text="unit.name"></option>
                                    </template>
                                </select>
                                <x-input-error :property="'organizations.' . $organization->id . '.unit_id'" />
                            </x-input-container>
                        @endif

                        <x-input-container x-show="isVisitor">
                            <x-message type="info"
                                       icon="fa-solid fa-circle-info">
                                Visitors are assigned to the top-level organization only. No region or unit selection is required.
                            </x-message>
                        </x-input-container>
                    </div>
                </div>
            @endforeach

            @error('organizations')
                <x-message type="danger" class="mt-3">{{ $message }}</x-message>
            @enderror

            <x-submit-container>
                <x-submit-button>
                    <i class="fa fa-fw fa-paper-plane"></i>
                    Resubmit Application
                </x-submit-button>
            </x-submit-container>

        </form>
    </x-card>

</x-slim-container>

@endsection

@section('page-script')
<script>
    window.$organization_hierarchy = @json($organization_hierarchy);
    window.$account_type = @json($account_type);
</script>
@endsection
