import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type FaqSection = {
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

        //         if (!this.deleting) {
        //             return;
        //         }

        //         this.submitting = true;

        //         router.post(
        //             this.deleteRoute(this.deleting),
        //             {},
        //             {
        //                 onFinish: () => {
        //                     this.submitting = false;
        //                     this.deleting = null;
        //                 },
        //             },
        //         );

        this.delete_section = null;
    };
}
