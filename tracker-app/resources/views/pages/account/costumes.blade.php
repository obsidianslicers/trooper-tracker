@extends('layouts.base')

@section('page-title', 'Trooper Costumes')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>
        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <div x-data="Account.Costumes.costumeSelector()"
                     class="card bg-dark text-white shadow-sm mb-4">
                    <div class="card-body">
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
                                        <label class="list-group-item bg-dark text-white border-secondary d-flex align-items-center py-3"
                                               style="cursor: pointer;">
                                            <input type="checkbox"
                                                   class="form-check-input me-3"
                                                   x-bind:value="org.id"
                                                   x-model="selectedOrgs">
                                            <div>
                                                <span class="d-block fw-bold"
                                                      x-text="org.organization.name"></span>
                                                <span class="text-muted small"
                                                      x-text="'Prefix: ' + (org.prefix || 'None')"></span>
                                            </div>
                                        </label>
                                    </template>
                                </template>
                            </div>

                            <template x-if="selectedCostume && selectedCostume.organization_costumes.length === 0">
                                <div class="text-danger small">No organizations found for this costume type.</div>
                            </template>
                        </div>

                        <div class="d-grid">
                            <button type="button"
                                    class="btn btn-primary"
                                    x-bind:disabled="!selectedCostume || selectedOrgs.length === 0"
                                    x-on:click="enlistCostume()">
                                <span x-show="!loading">
                                    Add to Armory
                                </span>
                                <span x-show="loading"
                                      class="fa fa-fw fa-spinner fa-spin me-2"
                                      role="status"></span>
                                <span x-show="loading">Submitting ...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <x-table>
                    <thead>
                        <tr>
                            <th>Attached Costume</th>
                            <th>Organizations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trooper_costumes as $trooper_costume)
                            <tr>
                                <td class="text-nowrap">{{ $trooper_costume->name }}</td>
                                <td>{{ $trooper_costume->display_organizations }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </form>
        </x-card>
    </x-slim-container>

@endsection

@section('page-script')
    <script>
        window.$costumes = @json($costumes);
    </script>
@endsection