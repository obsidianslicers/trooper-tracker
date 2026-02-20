export default function costumeSelector() {
    return {
        search: '',
        showResults: false,
        registry: window.$costumes || [],
        selectedCostume: null,
        selectedOrgs: [],

        get filteredCostumes() {
            const query = this.search.toLowerCase();
            if (query.length < 2) return [];
            return this.registry.filter(c => c.name.toLowerCase().includes(query));
        },

        init() {
            // We listen on the document because HTMX fires there.
            // Arrow function ensures 'this' refers to the Alpine component.
            document.addEventListener('htmx:afterRequest', (event) => {
                // Check if the request was successful and if it was OUR form
                // (optional: check event.target to ensure it's the right form)
                if (event.detail.successful) {
                    this.resetForm();
                }
            });
        },

        resetForm() {
            this.search = '';
            this.selectedCostume = null;
            this.selectedOrgs = [];
            this.showResults = false;
        },

        selectCostume(costume) {
            this.selectedCostume = costume;
            this.search = costume.name;
            this.showResults = false;
            this.selectedOrgs = [];

            // Efficiency Protocol: If there is only one organization, auto-select it.
            if (costume.organization_costumes && costume.organization_costumes.length === 1) {
                this.selectedOrgs.push(costume.organization_costumes[0].id);
            }
        },
    }
}