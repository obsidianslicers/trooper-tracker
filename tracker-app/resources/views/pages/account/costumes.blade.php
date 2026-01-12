@extends('layouts.base')

@section('page-title', 'Trooper Costumes')

@section('content')

    @include('pages.account.tabs')

        <x-slim-container>

        <x-card>
            <form method="POST"
                novalidate="novalidate">
                @csrf

                <div x-data="Account.Costumes.costumeSelector({ organizations: window.$organization_costumes })">
                
                    <x-input-container>
                        @if($organization_costumes->count() == 0)
                            <x-message :type="'danger'">
                                You do not have any assigned organizations
                            </x-message>
                        @else
                            <x-input-select :property="'organization_id'"
                                            :options="$organization_costumes->pluck('name', 'id')->toArray()"
                                            :placeholder="'-- Select your Organization --'"
                                            x-on:change="updateCostumes"
                                            x-model="organizationId" />
                        @endif
                    </x-input-container>

                    @if($organization_costumes->count() > 0)
                        <x-input-container>
                            <x-input-select :property="'costume_id'"
                                            x-bind:disabled="!organizationId"
                                            hx-post="{{ route('account.costumes-htmx') }}"
                                            hx-select="#trooper-costumes-table"
                                            hx-target="#trooper-costumes-table"
                                            hx-swap="outerHTML"
                                            hx-indicator="#transmission-bar-trooper-costumes"
                                            hx-include="closest form">
                                <option value="">-- Select your Costume --</option>
                                <template x-for="costume in costumes" x-bind:key="costume.id">
                                    <option x-bind:value="costume.id" x-text="costume.name"></option>
                                </template>
                            </x-input-select>
                        </x-input-container>
                    @endif
            </div>

            @include('pages.account.costumes-table', compact('trooper_costumes')) 

        </x-card>

    </x-slim-container>

@endsection

@section('page-script')
<script>
    window.$organization_costumes = @json($organization_costumes);
</script>
@endsection