export default function eventSelector() {
    return {
        // Alpine state
        form: {
            search_term: '',
            organization_id: '',
            costume_organization_id: '',
        },

        init() {
            const picker = this.$refs.organizationPicker;

            const hidden = picker.querySelector('#organization_id');

            const observer = new MutationObserver(() => {
                this.form.organization_id = parseInt(hidden.value);
            });

            observer.observe(hidden, { attributes: true, attributeFilter: ['value'] });
        },

        // Core filter logic
        matches(eventEl) {
            const name = eventEl.dataset.eventName?.toLowerCase() ?? '';
            const hostingOrganizationId = eventEl.dataset.eventHostingOrganizationId ?? '';

            // Collect costume org IDs from <li data-costume-org="X">
            const costumeOrganizations = Array.from(eventEl.querySelectorAll('[data-event-status="1"][data-event-costume-organization-id]')).map(el => el.dataset.eventCostumeOrganizationId);

            if (this.form.search_term.length > 0 && !name.includes(this.form.search_term.toLowerCase())) {
                return false;
            }

            // Hosting organization filter
            if (this.form.organization_id && hostingOrganizationId != this.form.organization_id) {
                return false;
            }

            // Costume organization filter
            if (this.form.costume_organization_id && !costumeOrganizations.includes(this.form.costume_organization_id)) {
                return false;
            }

            return true;
        }
    }
}
