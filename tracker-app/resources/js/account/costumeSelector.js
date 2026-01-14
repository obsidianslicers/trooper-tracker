export default function ({ organizations }) {
    return {
        organizations,
        organizationId: '',
        costumes: [],

        updateCostumes() {
            const organization = this.organizations.find(o => o.id == this.organizationId);
            this.costumes = organization ? organization.organization_costumes : [];
        }
    }
}
