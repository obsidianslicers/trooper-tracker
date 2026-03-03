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
                        Legal Name:
                    </x-label>
                    <x-input-text autofocus
                                  :property="'legal_name'" />
                    <x-input-help>
                        Used for official records and communications with event staff and coordinators.
                        This will not be displayed publicly on your profile or on the dashboard, but will be
                        shared with event coordinators for safety and accountability reasons.
                    </x-input-help>
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Display Name:
                   </x-label>
                    <x-input-text autofocus
                                  :property="'display_name'" />
                    <x-input-help>
                        This is the name that will be shown publicly on your profile and on the dashboard.
                    </x-input-help>
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Email:
                    </x-label>
                    <x-input-text :property="'email'"
                                  :value="$email"
                                  x-bind:disabled="$registration_method != 'email'" />
                </x-input-container>

                <x-input-container x-data>
                    <x-label>
                        Phone (Optional):
                    </x-label>
                    <x-input-text :property="'phone'"
                                  x-mask="(999) 999-9999" />
                </x-input-container>

                @if($registration_method == 'email')
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
                                        <x-input-text :property="'organizations.' . $organization->id . '.identifier'" />
                                    </div>
                                </x-input-container>
                            @endif

                            @if(count($organization->regions) > 0)
                                <x-input-container>
                                    <select name="organizations[{{ $organization->id }}][region_id]"
                                        x-model="regionId"
                                        x-on:change="updateUnits"
                                        class="form-select">
                                        <option value="">-- Select your Region/Garrison --</option>
                                        <template x-for="region in regions" x-bind:key="region.id">
                                            <option x-bind:value="region.id" x-text="region.name"></option>
                                        </template>
                                    </select>
                                </x-input-container>

                                <x-input-container>
                                    <select name="organizations[{{ $organization->id }}][unit_id]"
                                            x-model="unitId"
                                            x-bind:disabled="!regionId"
                                            class="form-select">
                                        <option value="">-- Select your Unit/Squad --</option>
                                        <template x-for="unit in units" x-bind:key="unit.id">
                                            <option x-bind:value="unit.id" x-text="unit.name"></option>
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

            </form>
        </x-card>
    </x-slim-container>

@endsection

@section('page-script')
<script>
    window.$organization_hierarchy = @json($organization_hierarchy);
</script>
@endsection