import { getCookie, setCookie } from '../custom/utils';
export default function eventSelector() {
    const cookieName = 'hosting_organization_ids';

    return {
        // Alpine state
        form: {
            search_term: '',
            hosting_organization_ids: [],
            costume_organization_id: '',
        },
        hosting_organization_all_ids: [],
        hosting_organization_labels: {},
        costume_organization_labels: {},

        init() {
            const selectedOrganizationIds = this.parseHostingOrganizationIds();
            this.form.hosting_organization_ids = selectedOrganizationIds;

            const hostingOrganizationData = this.$refs.hostingOrganizationList?.dataset.hostingOrganizationIds;
            if (hostingOrganizationData) {
                try {
                    const parsedHostingOrganizationIds = JSON.parse(hostingOrganizationData);
                    this.hosting_organization_all_ids = parsedHostingOrganizationIds
                        .map((id) => id.toString())
                        .filter((id) => id.length > 0);
                } catch (_error) {
                    this.hosting_organization_all_ids = [];
                }
            }

            const hostingOrganizationLabels = this.$refs.hostingOrganizationList?.dataset.hostingOrganizationLabels;
            if (hostingOrganizationLabels) {
                try {
                    this.hosting_organization_labels = JSON.parse(hostingOrganizationLabels);
                } catch (_error) {
                    this.hosting_organization_labels = {};
                }
            }

            const costumeOrganizationLabels = this.$refs.costumeOrganizationSelect?.dataset.costumeOrganizationLabels;
            if (costumeOrganizationLabels) {
                try {
                    this.costume_organization_labels = JSON.parse(costumeOrganizationLabels);
                } catch (_error) {
                    this.costume_organization_labels = {};
                }
            }
        },

        parseHostingOrganizationIds() {
            const selectedOrganizationIds = getCookie(cookieName) ?? getCookie('hosting_organization_id');

            if (!selectedOrganizationIds) {
                return [];
            }

            return selectedOrganizationIds
                .split(',')
                .map((id) => parseInt(id, 10))
                .filter((id) => Number.isInteger(id) && id > 0)
                .map((id) => id.toString());
        },

        hasActiveHostingFilter() {
            const selectedCount = this.form.hosting_organization_ids.length;
            if (selectedCount === 0) {
                return false;
            }

            const allCount = this.hosting_organization_all_ids.length;
            if (allCount === 0) {
                return true;
            }

            return selectedCount < allCount;
        },

        getActiveHostingOrganizationIds() {
            if (!this.hasActiveHostingFilter()) {
                return [];
            }

            return this.form.hosting_organization_ids;
        },

        persistHostingOrganizations() {
            if (this.hasActiveHostingFilter()) {
                setCookie(cookieName, this.form.hosting_organization_ids.join(','));
                return;
            }

            setCookie(cookieName, '');
        },

        selectAllHostingOrganizations() {
            const fallbackIds = Array.from(this.$el.querySelectorAll('[data-hosting-organization-checkbox]'))
                .map((el) => el.value)
                .filter((value) => value !== '');

            const selectedIds = this.hosting_organization_all_ids.length > 0
                ? this.hosting_organization_all_ids
                : fallbackIds;

            this.form.hosting_organization_ids = [...new Set(selectedIds)];
            this.persistHostingOrganizations();
        },

        clearHostingOrganizations() {
            this.form.hosting_organization_ids = [];
            this.persistHostingOrganizations();
        },

        removeHostingOrganization(hostingOrganizationId) {
            this.form.hosting_organization_ids = this.form.hosting_organization_ids
                .filter((id) => id !== hostingOrganizationId);
            this.persistHostingOrganizations();
        },

        getHostingOrganizationLabel(hostingOrganizationId) {
            return this.hosting_organization_labels[hostingOrganizationId] ?? `Organization ${hostingOrganizationId}`;
        },

        clearCostumeOrganization() {
            this.form.costume_organization_id = '';
        },

        getCostumeOrganizationLabel() {
            const selectedCostumeOrganizationId = this.form.costume_organization_id;

            if (!selectedCostumeOrganizationId) {
                return '';
            }

            const selectedCostumeOrganizationIdAsString = selectedCostumeOrganizationId.toString();
            const labelFromMap = this.costume_organization_labels[selectedCostumeOrganizationIdAsString]
                ?? this.costume_organization_labels[selectedCostumeOrganizationId];
            if (labelFromMap) {
                return labelFromMap;
            }

            const costumeSelect = this.$refs.costumeOrganizationSelect;
            if (costumeSelect) {
                const selectedOption = Array.from(costumeSelect.options)
                    .find((option) => option.value === selectedCostumeOrganizationIdAsString);

                if (selectedOption?.text) {
                    return selectedOption.text.trim();
                }
            }

            return 'Requested Character';
        },

        clearAllFilters() {
            this.form.search_term = '';
            this.form.costume_organization_id = '';
            this.clearHostingOrganizations();
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
            const activeHostingOrganizationIds = this.getActiveHostingOrganizationIds();
            if (activeHostingOrganizationIds.length > 0
                && !activeHostingOrganizationIds.includes(hostingOrganizationId)) {
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
