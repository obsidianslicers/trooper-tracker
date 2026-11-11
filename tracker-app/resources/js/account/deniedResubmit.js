// The club picker on the denied application page (pages/account/denied.blade.php): ticking a
// club shows its ID, region and unit fields; picking a region fills its units.
function organizationFor(organizationId) {
    return (window.$organization_hierarchy || []).find((org) => org.id === organizationId) || {};
}

export function registration() {
    return {
        selected: {},

        handleOrgToggled(id, active) {
            this.selected[id] = active;
        },
    };
}

export function organizationSelector({ organizationId }) {
    return {
        active: false,
        isVisitor: window.$account_type === 'visitor',
        regions: [],
        units: [],
        regionId: '',
        unitId: '',

        init() {
            const organization = organizationFor(organizationId);

            this.active = !!organization.selected;
            this.regions = organization.regions || [];
            this.units = this.unitsFor(organization.region_id);

            // the options render after this, so pick the saved region and unit once they exist
            this.$nextTick(() => {
                this.regionId = organization.region_id ? String(organization.region_id) : '';
                this.unitId = organization.unit_id ? String(organization.unit_id) : '';
            });
        },

        toggle(event) {
            this.active = event.target.checked;
            this.$dispatch('organization-toggled', { id: organizationId, active: this.active });
        },

        updateUnits() {
            this.units = this.unitsFor(this.regionId);
            this.unitId = '';
        },

        unitsFor(regionId) {
            const region = this.regions.find((r) => String(r.id) === String(regionId));

            return region ? region.units || [] : [];
        },
    };
}
