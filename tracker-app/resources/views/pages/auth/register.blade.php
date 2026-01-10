@extends('layouts.base')

@section('page-title', 'Register')

@section('content')

    <x-slim-container class="mt-4">

        <x-card>

            <x-message>
                <b>New to the 501st and/or {{ config('app.name') }}?</b> Or are you solely a member of another organization?
                Use this form below to start signing up for troops.
                <p class="mt-3 mb-0">
                    <i>Command Staff will need to approve your account prior to use.</i>
                </p>
            </x-message>

            <form action="{{ route('auth.register') }}"
                  method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Display Name (first &amp; last name, or use a nickname):
                    </x-label>
                    <x-input-text autofocus
                                  :property="'name'" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Email:
                    </x-label>
                    <x-input-text :property="'email'"
                                  :value="$email" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Phone (Optional):
                    </x-label>
                    <x-input-text :property="'phone'" />
                </x-input-container>

                @if(old('registration_method', $registration_method) == 'email')
                    <x-input-container>
                        <x-label>
                            Password:
                        </x-label>
                        <x-input-password :property="'password'" />
                    </x-input-container>
                @endif

                <x-input-container>
                    <x-label>
                        Account Type:
                    </x-label>
                    <x-input-select :property="'account_type'"
                                    :options="['member' => 'Member', 'handler' => 'Handler']"
                                    :placeholder="'-- Select your Account Type --'" />
                    <x-input-help>
                        Are you a member of an organization selected below, or
                        would you like to be assigned as a handler to an organization?
                    </x-input-help>
                </x-input-container>

                <p>
                    Select your associated organizations below.
                </p>

                <x-transmission-bar :id="'register-organization'" />

                @foreach ($organizations as $organization)
                    @php($account_type = old('account_type', request('account_type', 'member')))
                    @php($organization_selected = old("organizations.{$organization->id}.selected", $organization->selected))

                    <div 
                        id="organization-selection-{{ $organization->id }}"
                        x-data="organizationSelector({ organizationId: {{ $organization->id }} })"
                        x-init="init()">
                        <x-input-container>
                            <x-input-checkbox 
                                :property="'organizations.' . $organization->id . '.selected'"
                                :label="$organization->name"
                                :value="'1'"
                                :checked="$organization_selected"
                                @change="toggle" />
                        </x-input-container>

                        <div 
                            class="organization-{{ $organization->id }} ps-4"
                            x-show="active"
                            x-transition>
                            @if($account_type !== 'handler')
                                <x-input-container>
                                    <div class="input-group pointer">
                                        <span class="input-group-text">
                                            {{ $organization->identifier_display }}:
                                        </span>
                                        <x-input-text :property="'organizations.' . $organization->id . '.identifier'" />
                                    </div>
                                </x-input-container>
                            @endif

                            @if($organization->organizations->count() > 0)
                                <x-input-container>
                                    <select 
                                        name="organizations[{{ $organization->id }}][region_id]"
                                        x-model="regionId"
                                        @change="updateUnits"
                                        class="form-select"
                                    >
                                        <option value="">-- Select your Region/Garrison --</option>

                                        <template x-for="region in regions" :key="region.id">
                                            <option :value="region.id" x-text="region.name"></option>
                                        </template>
                                    </select>
                                </x-input-container>

                                <x-input-container id="unit-container-{{ $organization->id }}">
                                    <select 
                                        name="organizations[{{ $organization->id }}][unit_id]"
                                        x-model="unitId"
                                        :disabled="!regionId"
                                        class="form-select"                >
                                        <option value="">-- Select your Unit/Squad --</option>

                                        <template x-for="unit in units" :key="unit.id">
                                            <option :value="unit.id" x-text="unit.name"></option>
                                        </template>
                                    </select>
                                </x-input-container>
                            @endif
                        </div>
                    </div>
  
                @endforeach

                <x-submit-container>
                    <x-submit-button>
                        Register
                    </x-submit-button>
                </x-submit-container>
                <br />

            </form>
        </x-card>
    </x-slim-container>

@endsection

@section('page-script')
<script>
    window.$organization_hierarchy = @json($organization_hierarchy);
    
    document.addEventListener('alpine:init', () => {
    Alpine.data('organizationSelector', ({ organizationId }) => ({
        active: false,
        regionId: '',
        unitId: '',
        regions: [],
        units: [],

        init() {
            // If server hydrated old values, preload them
            const organization = window.$organization_hierarchy.find(o => o.id === organizationId);

            if (organization.preselected) {
                this.active = true;
                this.regions = organization.regions;
                this.regionId = organization.region_id ?? '';
                this.updateUnits();
                this.unitId = organization.unit_id ?? '';
            }
        },

        toggle() {
            this.active = !this.active;

            if (this.active) {
                const organization = window.$organization_hierarchy.find(o => o.id === organizationId);
                this.regions = organization.regions;
            } else {
                this.regionId = '';
                this.unitId = '';
                this.regions = [];
                this.units = [];
            }
        },

        updateUnits() {
            const region = this.regions.find(r => r.id == this.regionId);
            this.units = region ? region.units : [];
            this.unitId = '';
        }
    }));
});
</script>
@endsection