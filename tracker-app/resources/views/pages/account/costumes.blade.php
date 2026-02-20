@extends('layouts.base')

@section('page-title', 'Trooper Costumes')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>
        <x-card>
            <form hx-post="{{ route('account.costumes-htmx') }}"
                  hx-target="#trooper-costumes-table"
                  hx-swap="outerHTML"
                  novalidate="novalidate">
                @csrf

                <div x-data="Account.Costumes.costumeSelector()"
                     class="mb-4">
                    <div class="mb-4 position-relative">
                        <x-label>
                            1. Find Your Costume
                        </x-label>
                        <x-input-text property="'costume_id'"
                                      placeholder="Search ... (e.g. Shdadow Scout)"
                                      x-model="search"
                                      x-on:focus="showResults = true"
                                      x-on:click.away="showResults = false" />

                        <div x-show="showResults && filteredCostumes.length > 0"
                             class="list-group position-absolute w-100 mt-1 shadow-lg"
                             style="z-index: 1050; max-height: 250px; overflow-y: auto;">
                            <template x-for="costume in filteredCostumes"
                                      x-bind:key="costume.id">
                                <button type="button"
                                        class="list-group-item list-group-item-action bg-dark text-white border-secondary"
                                        x-on:click="selectCostume(costume)"
                                        x-text="costume.name"></button>
                            </template>
                        </div>
                    </div>

                    <div x-show="selectedCostume"
                         x-transition
                         class="mb-4">
                        <x-label>
                            2. Select Approved Organizations
                        </x-label>

                        <div class="list-group">
                            <template x-if="selectedCostume && selectedCostume.organization_costumes.length > 0">
                                <template x-for="org in selectedCostume.organization_costumes"
                                          x-bind:key="org.id">
                                    <label class="list-group-item d-flex align-items-center py-3 pointer">
                                        <input type="checkbox"
                                               class="form-check-input me-3"
                                               name="organization_costume_ids[]"
                                               x-bind:value="org.id"
                                               x-model="selectedOrgs">
                                        <div>
                                            <span class="d-block"
                                                  x-text="org.organization.name"></span>
                                        </div>
                                    </label>
                                </template>
                            </template>
                        </div>

                        <template x-if="selectedCostume && selectedCostume.organization_costumes.length === 0">
                            <div class="text-danger small">No organizations found for this costume type.</div>
                        </template>
                    </div>

                    <div class="row">
                        <div class="col text-end">
                            <x-submit-button x-bind:disabled="!selectedCostume || selectedOrgs.length === 0">
                                Add to Armory
                            </x-submit-button>
                        </div>
                    </div>
                </div>
            </form>

            @include('pages.account.costumes-table', compact('trooper_costumes'))

        </x-card>
    </x-slim-container>

@endsection

@section('page-script')
    <script>
        window.$costumes = @json($costumes);
    </script>
@endsection