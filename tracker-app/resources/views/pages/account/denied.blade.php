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
                They will be restored to pending status when you resubmit.
            </p>
            <ul class="list-group list-group-flush">
                @foreach($denied_requests as $request)
                    @php
                        $org = $request->organization;
                        $path_ids = collect(array_filter(explode(':', trim($org->node_path, ':'))));
                        $path_names = $path_ids->map(fn($id) => $ancestors[$id]?->name ?? '?');
                    @endphp
                    <li class="list-group-item px-0">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa fa-fw fa-circle-xmark text-danger"></i>
                            <span>{{ $path_names->implode(' — ') }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-card>
        <h6 class="mb-3">Resubmit Your Application</h6>

        <form method="POST"
              action="{{ route('account.denied.resubmit') }}"
              novalidate="novalidate">
            @csrf

            @if($available_clubs->isNotEmpty())
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
                        Optionally select a different or additional club before resubmitting.
                        Leave blank to resubmit with your existing club selection above.
                    </p>

                    <x-input-container>
                        <x-label>Change Club / Organization (optional):</x-label>
                        <select name="organization_id"
                                id="organization_id"
                                x-model="selectedId"
                                class="form-select @error('organization_id') is-invalid @enderror">
                            <option value="">— Keep existing selection —</option>
                            @foreach($available_clubs->groupBy(fn($org) => explode(':', $org->node_path)[0]) as $root_id => $orgs)
                                @php($root_org = $orgs->firstWhere('depth', 0))
                                @php($child_orgs = $orgs->where('depth', '>', 0)->values())
                                @if($root_org)
                                    <option value="{{ $root_org->id }}">{{ $root_org->name }}</option>
                                @endif
                                @if($child_orgs->isNotEmpty())
                                    @php($root_name = $orgs->first()?->name ?? 'Other')
                                    <optgroup label="{{ $root_name }}">
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
                            <x-label>
                                <span x-text="selectedOrg?.identifier_display ?? 'Member ID'">Member ID</span>
                                <span class="text-muted" x-show="!isIdentifierRequired"> (optional)</span>:
                            </x-label>
                            <input type="text"
                                   name="identifier"
                                   id="identifier"
                                   class="form-control @error('identifier') is-invalid @enderror"
                                   :placeholder="selectedOrg?.identifier_display ?? 'Member ID'"
                                   :required="isIdentifierRequired"
                                   maxlength="64"
                                   value="{{ old('identifier') }}" />
                            <x-input-error :property="'identifier'" />
                        </x-input-container>
                    </div>

                </div>
            @else
                <p class="text-muted mb-3">
                    Resubmit your application for reconsideration.
                    A moderator will review it and you will be notified of the decision.
                </p>
            @endif

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
