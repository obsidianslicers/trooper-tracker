@extends('layouts.base')

@section('page-title', 'Recruit Trooper')

@section('content')

    <x-slim-container>

        <x-card>

            <form method="POST"
                  action="{{ route('admin.troopers.recruit') }}"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Trooper
                    </x-label>
                    <x-input-picker :property="'trooper_id'"
                                    :route="'pickers.trooper'"
                                    :params="['moderated_only' => false, 'active_only' => true]"
                                    :text="old('trooper_name', '(Search for a Trooper)')"
                                    :value="old('trooper_id')" />
                    <x-input-error :property="'trooper_id'" />
                </x-input-container>

                <div x-data="{
                    orgs: {{ Js::from($organizations_data) }},
                    selectedId: '{{ old('organization_id') }}',
                    isVisitorTrooper: false,
                    init() {
                        document.addEventListener('picker-selected', (e) => {
                            if (e.detail.property === 'trooper_id') {
                                this.isVisitorTrooper = e.detail.isVisitor;
                                this.selectedId = '';
                            }
                        });
                    },
                    get filteredOrgs() {
                        return this.isVisitorTrooper ? this.orgs.filter(o => o.depth === 0) : this.orgs;
                    },
                    get selectedOrg() {
                        const id = parseInt(this.selectedId);
                        return this.orgs.find(o => o.id === id) ?? null;
                    },
                    get isIdentifierRequired() {
                        if (!this.selectedOrg?.identifier_validation) return false;
                        return this.selectedOrg.identifier_validation.split('|').includes('required');
                    }
                }">

                    <x-input-container>
                        <x-label>Assign to Unit:</x-label>
                        <select name="organization_id"
                                id="organization_id"
                                x-model="selectedId"
                                class="form-select @error('organization_id') is-invalid @enderror">
                            <option value="">— Select a Unit —</option>
                            <template x-for="org in filteredOrgs" :key="org.id">
                                <option :value="org.id"
                                        x-text="'— '.repeat(org.depth) + org.name"
                                        :selected="selectedId == org.id">
                                </option>
                            </template>
                        </select>
                        <x-input-error :property="'organization_id'" />
                    </x-input-container>

                    <div x-show="selectedId && selectedOrg" x-cloak>
                        <x-input-container>
                            <x-label>
                                <span x-text="selectedOrg?.identifier_display ?? 'Member ID'">Member ID</span><span class="text-muted" x-show="!isIdentifierRequired"> (optional)</span>:
                            </x-label>
                            <input type="text"
                                   name="identifier"
                                   id="identifier"
                                   class="form-control @error('identifier') is-invalid @enderror"
                                   value="{{ old('identifier') }}"
                                   :placeholder="selectedOrg?.identifier_display ?? 'Member ID'"
                                   maxlength="64" />
                            <x-input-error :property="'identifier'" />
                        </x-input-container>
                    </div>

                </div>

                <x-modal-picker :label="'Find a Trooper'" />

                <x-submit-container>
                    <x-submit-button>Add to Roster</x-submit-button>
                    <x-link-button-cancel :url="route('admin.troopers.list')" />
                </x-submit-container>

            </form>

        </x-card>

    </x-slim-container>

@endsection
