export default function () {
    return {
        activeOrganizationIds: [],
        account_type: window.$account_type || 'member',

        // Computed property to check if any active org requires a guardian
        get requiresGuardian() {
            return window.$organization_hierarchy
                .filter(org => this.activeOrganizationIds.includes(org.id))
                .some(org => org.requires_guardian);
        },

        handleOrgToggled(id, isActive) {
            if (isActive) {
                if (!this.activeOrganizationIds.includes(id)) this.activeOrganizationIds.push(id);
            } else {
                this.activeOrganizationIds = this.activeOrganizationIds.filter(orgId => orgId !== id);
            }
        }
    }
}
