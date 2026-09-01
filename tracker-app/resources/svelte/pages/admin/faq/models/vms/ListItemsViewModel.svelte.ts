import { type Paginated, ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { FaqItem, FaqSection } from "../types";

export type FaqListPageData = {
    items: FaqItem[] | Paginated<FaqItem>;
    sections: FaqSection[];
    section_id: number | null;
    sortable: boolean;
}

export class ListItemsViewModel extends ViewModel {
    deleting: boolean = $state(false);
    delete_item: FaqItem | null = $state(null);
    items: FaqItem[] | Paginated<FaqItem> = $state([]);
    section_id: number | null = $state(null);
    sortable: boolean = $state(false);

    constructor(pageData: FaqListPageData) {
        super();

        this.section_id = pageData.section_id;
        this.sortable = pageData.sortable;
        this.items = pageData.items;
    }

    get show_delete_confirmation(): boolean { return this.delete_item !== null; }

    reorder = (ordered_ids: number[]) => {
        const url = getRoute("admin.faq.reorder");

        const data = { ids: ordered_ids };

        const options = {
            preserveUrl: true,
            preserveState: true,
            preserveScroll: true
        };

        router.post(url, data, options);
    };

    confirmDelete = (item: FaqItem) => {
        this.delete_item = item;
    };

    cancelDelete = () => {
        this.delete_item = null;
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

        this.delete_item = null;
    };

    // protected deleteRoute(item: FaqItem): string {
    //     return getRoute("admin.faq.delete", { faq: item.id });
    // }
}



// export abstract class DeletableListViewModel<T extends { id: number }> extends ViewModel {
//     deleting: T | null = $state(null);
//     submitting: boolean = $state(false);

//     protected abstract deleteRoute(item: T): string;

//     get show(): boolean {
//         return this.deleting !== null;
//     }

//     set show(value: boolean) {
//         if (!value) {
//             this.deleting = null;
//         }
//     }

//     confirmDelete = (item: T) => {
//         this.deleting = item;
//     };

//     cancelDelete = () => {
//         this.deleting = null;
//     };

//     delete = (e: Event) => {
//         e.preventDefault();

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
//     };
// }
