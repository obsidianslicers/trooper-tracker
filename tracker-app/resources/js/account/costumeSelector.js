export default function costumeSelector() {
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