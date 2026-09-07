import { ViewModel } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type FaqItem = {
    id: number;
    title: string;
    has_video: boolean;
    sort_order: number;
    update_route?: string;
};

export type FaqSection = {
    id: number;
    label: string;
    icon: string;
    sort_order: number;
    faqs: FaqItem[];
    update_route?: string;
    create_item_route?: string;
};

export type IndexPageData = {
    sections: FaqSection[];
};

export class IndexViewModel extends ViewModel {
    deleting: boolean = $state(false);
    delete_section: FaqSection | null = $state(null);
    delete_item: FaqItem | null = $state(null);
    sections: FaqSection[] = $state([]);
    expand_section_id: number | null = $state(null);

    constructor(public pageData: IndexPageData) {
        super();
        if (pageData.sections.length > 0) {
            this.sections = pageData.sections;
            this.buildSections();
        }
    }

    get showing_sections(): boolean { return this.expand_section_id === null; }
    get showing_items(): boolean { return this.expand_section_id !== null; }

    get show_delete_section(): boolean { return this.delete_section !== null; }
    get show_delete_item(): boolean { return this.delete_item !== null; }

    private buildSections() {
        this.sections.forEach((s) => {
            s.update_route = getRoute("admin.faq.sections.update", { section: s.id });
            s.create_item_route = getRoute("admin.faq.items.create", {
                section_id: s.id,
            });

            s.faqs.forEach((f) => {
                f.update_route = getRoute("admin.faq.items.update", { item: f.id });
            });
        });
    }

    cancelDelete = () => {
        this.delete_section = null;
        this.delete_item = null;
    };

    toggleSection = (section_id: number) => {
        if (this.showing_sections) {
            this.expand_section_id = section_id;
        } else {
            this.expand_section_id = null;
        }
    };

    reorder = (ordered_ids: number[]) => {
        const url = this.showing_sections
            ? getRoute("admin.faq.sections.reorder")
            : getRoute("admin.faq.items.reorder");

        const data = { ids: ordered_ids };

        const options = createPartialReloadOptions({
            onSuccess: () => {
                const message = this.showing_sections
                    ? "Sections reordered successfully."
                    : "FAQ items reordered successfully.";

                toastStateSvelte.success(message);
            }
        });

        router.post(url, data, options);
    };

    deleteSection = (e: Event) => {
        e.preventDefault();

        if (!this.show_delete_section || !this.delete_section) {
            return;
        }

        this.deleting = true;

        const section_id = this.delete_section.id;

        const url = getRoute("admin.faq.sections.delete", { section: this.delete_section.id });

        const data = {};

        const options = createPartialReloadOptions({
            onFinish: () => {
                this.deleting = false;
                this.delete_section = null;
                this.sections = this.sections.filter((s) => s.id !== section_id);
            },
        });

        router.post(url, data, options);
    };
}
