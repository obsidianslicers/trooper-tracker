export default function costumeSearch() {
    return {
        search: '',
        showResults: false,
        registry: window.$costumesList || [],
        selectedCostume: null,

        get filteredCostumes() {
            const q = this.search.toLowerCase();
            return q.length < 2 ? [] : this.registry.filter(c => c.name.toLowerCase().includes(q));
        },

        selectCostume(costume) {
            this.selectedCostume = costume;
            this.search = costume.name;
            this.showResults = false;

            const params = new URLSearchParams(window.location.search);
            htmx.ajax('GET', `/service-records/costumes/${costume.id}?${params}`, {
                target: '#costume-stats-panel',
                swap: 'innerHTML',
            });
        },
    };
}
