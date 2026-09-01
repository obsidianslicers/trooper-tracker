import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

type FaqSection = {
    id: number;
    label: string;
    icon: string;
    sort_order: number;
    faqs_count: number;
    update_route?: string;
};

export type ListSectionsPageData = {
    sections: FaqSection[];
};

export class ListSectionsViewModel extends ViewModel {
    deleting: boolean = $state(false);
    delete_section: FaqSection | null = $state(null);
    sections: FaqSection[] = $state([]);

    constructor(public pageData: ListSectionsPageData) {
        super();
        if (pageData.sections.length > 0) {
            this.sections = pageData.sections;

            this.sections.forEach((s) => {
                s.update_route = getRoute("admin.faq.sections.update", { section: s.id });
            });
        }
    }

    get show_delete_confirmation(): boolean { return this.delete_section !== null; }

    reorder = (ordered_ids: number[]) => {
        const url = getRoute("admin.faq.sections.reorder");

        const data = { ids: ordered_ids };

        const options = {
            preserveUrl: true,
            preserveState: true,
            preserveScroll: true
        };

        router.post(url, data, options);
    };

    confirmDelete = (item: FaqSection) => {
        this.delete_section = item;
    };

    cancelDelete = () => {
        this.delete_section = null;
    };

    delete = (e: Event) => {
        e.preventDefault();

        if (!this.show_delete_confirmation || !this.delete_section) {
            return;
        }

        this.deleting = true;

        const url = getRoute("admin.faq.sections.delete", { section: this.delete_section.id });

        const data = {};

        const options = {
            preserveUrl: true,
            preserveState: true,
            preserveScroll: true,
            only: ['flash', 'results'],
            onFinish: () => {
                this.deleting = false;
                this.delete_section = null;
            },
        };

        router.post(url, data, options);
    };
}
