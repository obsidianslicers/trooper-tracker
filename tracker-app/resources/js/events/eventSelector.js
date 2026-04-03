import { getCookie, setCookie } from '../custom/utils';
export default function eventSelector() {
    const cookieName = 'hosting_organization_id';

    return {
        // Alpine state
        form: {
            search_term: '',
            hosting_organization_id: '',
            costume_organization_id: '',
        },

        init() {
            const selectedOrganizationId = getCookie(cookieName);
            if (selectedOrganizationId) {
                this.form.hosting_organization_id = parseInt(selectedOrganizationId);
            }

            const hostingOrganizationSelect = this.$refs.hostingOrganizationPicker.querySelector('select');

            if (hostingOrganizationSelect) {
                // Ensure the select's visual state matches the cookie
                if (selectedOrganizationId) {
                    hostingOrganizationSelect.value = selectedOrganizationId;
                }

                // 3. Listen for the 'change' event instead of observing mutations
                hostingOrganizationSelect.addEventListener('change', (e) => {
                    const pickedValue = parseInt(e.target.value);
                    this.form.hosting_organization_id = pickedValue;

                    // 4. Update the cookie
                    if (pickedValue) {
                        setCookie(cookieName, pickedValue);
                    } else {
                        // Optional: Clear cookie if "Please Select" is chosen
                        setCookie(cookieName, '', -1);
                    }
                });
            }
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
            if (this.form.hosting_organization_id && hostingOrganizationId != this.form.hosting_organization_id) {
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
