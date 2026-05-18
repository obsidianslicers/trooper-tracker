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
                            @foreach($grouped as $root_id => $orgs)
                                @php($root_org = $orgs->firstWhere('depth', 0))
                                @php($child_orgs = $orgs->where('depth', '>', 0)->values())
                                @if($root_org)
                                    <option value="{{ $root_org->id }}" @selected(old('organization_id') == $root_org->id)>
                                        {{ $root_org->name }}
                                    </option>
                                @endif
                                @if($child_orgs->isNotEmpty())
                                    <optgroup label="{{ $root_orgs[$root_id]?->name ?? 'Other' }}">
                                        @foreach($child_orgs as $org)
                                            <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>
                                                {{ str_repeat('— ', $org->depth) }}{{ $org->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
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
