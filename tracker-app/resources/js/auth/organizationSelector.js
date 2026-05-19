export default function ({ organizationId }) {
    return {
        active: false,
        regionId: '',
        unitId: '',
        regions: [],
        units: [],
        isVisitor: window.$account_type === 'visitor',

        init() {
            const org = window.$organization_hierarchy.find(o => o.id === organizationId)
            if (!org) {
                return;
            }

            if (org.selected) {
                this.active = true
                this.regions = org.regions

                // Use $nextTick to ensure x-for renders region
                // options before x-model binds
                this.$nextTick(() => {
                    this.regionId = org.region_id ?? ''
                    this.updateUnits()
                    this.unitId = org.unit_id ?? ''
                })
            }

            // Report initial state to parent
            this.$dispatch('organization-toggled', { id: organizationId, active: this.active });

            // Keep isVisitor in sync when account type changes
            window.addEventListener('account-type-changed', (e) => {
                this.isVisitor = e.detail === 'visitor';
            });
        },

        toggle() {
            this.active = !this.active

            const org = window.$organization_hierarchy.find(o => o.id === organizationId)
            if (!org) {
                return;
            }

            if (this.active) {
                this.regions = org.regions
            } else {
                this.regionId = ''
                this.unitId = ''
                this.regions = []
                this.units = []
            }

            // Notify the parent
            this.$dispatch('organization-toggled', { id: organizationId, active: this.active });
        },

        updateUnits() {
            const region = this.regions.find(r => r.id == this.regionId)
            this.units = region ? region.units : []
            this.unitId = ''
        }
    }
}
