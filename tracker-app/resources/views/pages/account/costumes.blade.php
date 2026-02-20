@extends('layouts.base')

@section('page-title', 'Trooper Costumes')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>
        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <div x-data="costumePicker()"
                     class="card bg-dark text-white shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Add Costume to Profile</h5>

                        <div class="mb-4 position-relative">
                            <x-label>
                                1. Find Your Costume
                            </x-label>
                            <input type="text"
                                   class="form-control"
                                   placeholder="Search ... (e.g. Shdadow Scout)"
                                   x-model="search"
                                   x-on:focus="showResults = true"
                                   x-on:click.away="showResults = false">

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
                             class="bg-dark p-3 rounded border border-secondary mb-4">
                            <label class="d-block text-muted small mb-3">2. Select Approved Organizations</label>
                            <div class="d-flex flex-wrap gap-2">
                                <template x-for="org in selectedCostume.organization_costumes"
                                          x-bind:key="org.oc_id">
                                    <div class="form-check form-check-inline p-0 m-0">
                                        <label class="btn btn-sm"
                                               x-bind:class="selectedOrgs.includes(org.oc_id) ? 'btn-info' : 'btn-outline-secondary'">
                                            <input type="checkbox"
                                                   class="d-none"
                                                   x-bind:value="org.oc_id"
                                                   x-model="selectedOrgs">
                                            <span x-text="org.organization.name"></span>
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="button"
                                    class="btn btn-success btn-lg fw-bold"
                                    x-bind:disabled="!selectedCostume || selectedOrgs.length === 0"
                                    x-on:click="enlistCostume()">
                                <span x-show="!loading">Add to Armory</span>
                                <span x-show="loading"
                                      class="spinner-border spinner-border-sm me-2"
                                      role="status"></span>
                                <span x-show="loading">Processing...</span>
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

        function costumePicker() {
            return {
                search: '',
                showResults: false,
                loading: false,
                registry: window.$costumes || [],
                selectedCostume: null,
                selectedOrgs: [],

                get filteredCostumes() {
                    const query = this.search.toLowerCase();
                    if (query.length < 2) return [];
                    return this.registry.filter(c => c.name.toLowerCase().includes(query));
                },

                selectCostume(costume) {
                    this.selectedCostume = costume;
                    this.search = costume.name;
                    this.showResults = false;
                    this.selectedOrgs = [];
                },

                enlistCostume() {
                    this.loading = true;

                    const payload = {
                        organization_costume_ids: this.selectedOrgs,
                        _token: document.querySelector('input[name="_token"]').value
                    };

                    console.log('Deploying Payload:', payload);
                }
            }
        }
    </script>
@endsection