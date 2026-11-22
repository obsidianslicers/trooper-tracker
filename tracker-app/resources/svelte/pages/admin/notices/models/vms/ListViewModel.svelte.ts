import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import type { ListPageData, NoticeRow, NoticeScope } from "../types";

export class ListViewModel extends ViewModel {
    notices: NoticeRow[] = $state([]);
    scope: NoticeScope = $state("active");
    organization: ListPageData["organization"] = $state(null);

    constructor(pageData: ListPageData) {
        super();

        this.notices = pageData.notices.data;
        this.scope = pageData.scope;
        this.organization = pageData.organization;
    }

    /** The list with one filter changed and the others kept. */
    listUrl(changes: { scope?: NoticeScope; organization_id?: number | null }): string {
        const query: Record<string, string | number> = {
            scope: changes.scope ?? this.scope,
        };

        const organization_id = "organization_id" in changes
            ? changes.organization_id
            : this.organization?.id;

        if (organization_id) {
            query.organization_id = organization_id;
        }

        return getRoute("admin.notices.list", query);
    }

    get create_url(): string {
        return getRoute("admin.notices.create", { organization_id: this.organization?.id });
    }

    updateUrl(notice: NoticeRow): string {
        return getRoute("admin.notices.update", { notice: notice.id });
    }

    copyUrl(notice: NoticeRow): string {
        return getRoute("admin.notices.create", { copy_id: notice.id });
    }
}
