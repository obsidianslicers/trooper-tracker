<div class="row mb-0">

    <div class="col-sm-12 col-md-8">
        <x-input-container>
            <x-input-text :property="'search_term'"
                          :placeholder="'Search Event Name ...'"
                          :value="null"
                          x-model="form.search_term" />
        </x-input-container>
    </div>
    <div class="col-sm-12 col-md-4">
        <button type="button"
                class="btn btn-outline-secondary w-100"
                data-bs-toggle="modal"
                data-bs-target="#event-filter-modal">
            Filter Options
        </button>
    </div>

</div>

<div class="row mb-3 mt-3 mt-md-0"
     x-show="hasActiveHostingFilter() || form.costume_organization_id"
     x-cloak>
    <div class="col-12 d-flex flex-wrap gap-2">
        <template x-if="form.costume_organization_id">
            <span class="badge bg-info small px-2 py-1 d-inline-flex align-items-center">
                <span class="text-white"
                      x-text="getCostumeOrganizationLabel()"></span>
                <button type="button"
                        class="border-0 bg-transparent text-white p-0 ms-1 lh-1"
                        aria-label="Remove requested character type filter"
                        x-on:click="clearCostumeOrganization()">
                    <i class="fa fa-times"></i>
                </button>
            </span>
        </template>

        <template x-if="hasActiveHostingFilter()">
            <template x-for="hostingOrganizationId in getActiveHostingOrganizationIds()"
                      :key="`hosting-chip-${hostingOrganizationId}`">
                <span class="badge bg-primary small px-2 py-1 d-inline-flex align-items-center">
                    <span class="text-white"
                          x-text="getHostingOrganizationLabel(hostingOrganizationId)"></span>
                    <button type="button"
                            class="border-0 bg-transparent text-white p-0 ms-1 lh-1"
                            aria-label="Remove hosting organization filter"
                            x-on:click="removeHostingOrganization(hostingOrganizationId)">
                        <i class="fa fa-times"></i>
                    </button>
                </span>
            </template>
        </template>
    </div>
</div>

<div class="modal fade"
     id="event-filter-modal"
     tabindex="-1"
     aria-labelledby="event-filter-modal-label"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"
                    id="event-filter-modal-label">
                    Event Filters
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <x-input-container>
                    <x-label>
                        Search Events:
                    </x-label>
                    <x-input-text :property="'search_term_modal'"
                                  :placeholder="'Search Event Name ...'"
                                  :value="null"
                                  x-model="form.search_term" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Requested Character Types:
                    </x-label>
                    <x-input-select :property="'costume_organization_id'"
                                    :options="$costume_organizations->pluck('name', 'id')->toArray()"
                                    :value="null"
                                    :placeholder="'-- Requested Characters --'"
                                    x-ref="costumeOrganizationSelect"
                                    data-costume-organization-labels='@json($costume_organizations->mapWithKeys(fn($organization) => [(string) $organization->id => $organization->name])->all())'
                                    x-on:change="persistCostumeOrganization()"
                                    x-model="form.costume_organization_id" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Hosting Organization:
                    </x-label>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                :disabled="!hasActiveHostingFilter()"
                                x-on:click="selectAllHostingOrganizations()">
                            <span x-text="hasActiveHostingFilter() ? 'Show All' : 'Showing All'"></span>
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                x-on:click="clearHostingOrganizations()">
                            Clear All
                        </button>
                    </div>
                    <div class="small text-muted mb-2"
                         x-text="hasActiveHostingFilter() ? `${getActiveHostingOrganizationIds().length} selected` : 'Showing all organizations'">
                    </div>
                    <div class="border rounded p-2"
                         x-ref="hostingOrganizationList"
                         data-hosting-organization-ids='@json($hosting_organizations->pluck("id")->map(fn($id) => (string) $id)->values()->all())'
                         data-hosting-organization-labels='@json($hosting_organizations->mapWithKeys(fn($organization) => [(string) $organization->id => $organization->indented_name])->all())'>



                        @foreach ($hosting_organizations as $key => $hosting_organization)
                            @if($hosting_organization->type === \App\Enums\OrganizationType::ORGANIZATION)
                                @if($key > 0)
                                    <hr class="my-2">
                                @endif
                                <x-input-container class="ps-4">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           data-hosting-organization-checkbox
                                           x-bind:id="`hosting-organization-${{ $hosting_organization->id }}`"
                                           x-model="form.hosting_organization_ids"
                                           value="{{ $hosting_organization->id }}"
                                           x-on:change="persistHostingOrganizations()">
                                    <label class="form-check-label ms-2"
                                           x-bind:for="`hosting-organization-${{ $hosting_organization->id }}`">
                                        {{ $hosting_organization->name }}
                                    </label>
                                    @foreach ($hosting_organization->organizations as $region)
                                        <x-input-container class="ps-4 mt-2">
                                            <input type="checkbox"
                                                   class="form-check-input"
                                                   data-hosting-organization-checkbox
                                                   x-bind:id="`hosting-organization-${{ $region->id }}`"
                                                   x-model="form.hosting_organization_ids"
                                                   value="{{ $region->id }}"
                                                   x-on:change="persistHostingOrganizations()">
                                            <label class="form-check-label ms-2"
                                                   x-bind:for="`hosting-organization-${{ $region->id }}`">
                                                {{ $region->name }}
                                            </label>
                                            @foreach ($region->organizations as $unit)
                                                <x-input-container class="ps-4 mt-2">
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           data-hosting-organization-checkbox
                                                           x-bind:id="`hosting-organization-${{ $unit->id }}`"
                                                           x-model="form.hosting_organization_ids"
                                                           value="{{ $unit->id }}"
                                                           x-on:change="persistHostingOrganizations()">
                                                    <label class="form-check-label ms-2"
                                                           x-bind:for="`hosting-organization-${{ $unit->id }}`">
                                                        {{ $unit->name }}
                                                    </label>
                                                </x-input-container>
                                            @endforeach
                                        </x-input-container>
                                    @endforeach
                                </x-input-container>
                            @endif
                        @endforeach
                    </div>
                </x-input-container>
            </div>
            <div class="modal-footer">
                <button type="button"
                        class="btn btn-outline-secondary"
                        x-on:click="clearAllFilters()">
                    Clear Filters
                </button>
                <button type="button"
                        class="btn btn-primary"
                        data-bs-dismiss="modal">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>