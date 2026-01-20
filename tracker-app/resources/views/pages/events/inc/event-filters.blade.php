<div class="row mb-3">

    <div class="col-sm-12 col-md-4">

        <x-input-container>
            <x-label>
                Search Events:
            </x-label>
            <x-input-text :property="'search_term'"
                          :placeholder="'Search Event Name ...'"
                          :value="null"
                          x-model="form.search_term" />
        </x-input-container>

    </div>
    <div class="col-sm-12 col-md-4">
        <x-input-container x-ref="organizationPicker">
            <x-label>
                Hosting Organization:
            </x-label>
            <x-input-picker :property="'organization_id'"
                            :route="'pickers.organization'"
                            :text="'Hosting Organization'"
                            :value="null" />
        </x-input-container>
        <x-modal-picker :label="'Select the Hosting Organization'" />
    </div>
    <div class="col-sm-12 col-md-4">
        <x-input-container>
            <x-label>
                Requested Character Types:
            </x-label>
            <x-input-select :property="'costume_organization_id'"
                            :options="$costume_organizations->pluck('name', 'id')->toArray()"
                            :value="null"
                            :placeholder="'-- Requested Characters --'"
                            x-model="form.costume_organization_id" />
        </x-input-container>
    </div>

</div>