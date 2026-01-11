export default function () {
    return {
        toggleOrganization(organizationId, value) {
            // Toggle all regions + units under this org
            document.querySelectorAll(`[data-organization-id="${organizationId}"]`).forEach(cb => cb.checked = value);
        },

        toggleRegion(regionId, value) {
            // Toggle all units under this region
            document.querySelectorAll(`[data-region-id="${regionId}"]`).forEach(cb => cb.checked = value);
        }
    }
}
