import { mandalorianMercsParser } from '../parsers/mandalorianMercsParser';
import { theLegionParser } from './parsers/theLegionParser';

export default function () {
    return {
        mode: 'email',
        sourceContent: '',
        organizationName: '',
        form: {
            organization_id: 0,
            organization_name: '',
            club_name: '',
        },

        init() {
            const picker = this.$refs.organizationPicker;

            const hidden = picker.querySelector('#organization_id');
            const label = picker.querySelector('[name="picker-organization_id"]');

            const observer = new MutationObserver(() => {
                this.form.organization_id = parseInt(hidden.value);
                this.form.organization_name = label.value;
                this.form.club_name = this.findRootOrganizationName();

                // Re-parse when org changes
                if (this.mode === 'email' && this.sourceContent.trim() !== '') {
                    this.parseSource();
                }
            });

            observer.observe(hidden, { attributes: true, attributeFilter: ['value'] });
            observer.observe(label, { attributes: true, attributeFilter: ['value'] });
        },

        parseSource() {
            const message = this.sourceContent;
            if (!message.trim()) {
                return;
            }
            let parsed = {};
            switch (this.form.club_name) {
                case 'Mandalorian Mercs':
                    parsed = mandalorianMercsParser(message);
                    break;
                case '501st Legion':
                    parsed = theLegionParser(message);
                    break;
                default:
                    // Unknown org — do nothing or clear form
                    parsed = {};
            }

            this.form = { ...this.form, ...parsed };
        },

        findRootOrganizationName() {
            const hierarchy = window.$organization_hierarchy || [];

            for (const org of hierarchy) {
                if (org.id === this.form.organization_id) {
                    return org.name;
                }

                for (const region of org.regions) {
                    if (region.id === this.form.organization_id) {
                        return org.name;
                    }
                    for (const unit of region.units) {
                        if (unit.id === this.form.organization_id) {
                            return org.name;
                        }
                    }
                }
            }

            return null;
        },
    };
}