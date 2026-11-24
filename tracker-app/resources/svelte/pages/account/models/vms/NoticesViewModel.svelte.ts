import type { UnreadNotice } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { NoticesPageData } from "../types";

export class NoticesViewModel {
    notices: UnreadNotice[] = $state([]);

    constructor(page_data: NoticesPageData) {
        this.notices = page_data.notices;
    }

    dismiss = (notice: UnreadNotice) => {
        this.notices = this.notices.filter((n) => n.id !== notice.id);

        router.post(getRoute("account.update-notice-as-read", { notice: notice.id }), {}, {
            preserveState: true,
            preserveScroll: true,
            only: ["flash"],
        });
    };
}
