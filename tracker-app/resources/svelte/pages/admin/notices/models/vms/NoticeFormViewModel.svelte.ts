import { SubmitableViewModel, type ISubmitableViewModel, type ITrooperStamps, type Option } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { INoticeForm, NoticeFormPageData } from "../types";

function generateForm(notice: NoticeFormPageData["notice"]): InertiaForm<INoticeForm> {
    return useForm<INoticeForm>({
        organization_id: notice.organization_id,
        type: notice.type,
        title: notice.title,
        starts_at: notice.starts_at,
        ends_at: notice.ends_at,
        message: notice.message,
    });
}

/** The create and update notice forms; a notice with an id is updated, one without is created. */
export class NoticeFormViewModel
    extends SubmitableViewModel<NoticeFormViewModel, INoticeForm>
    implements ISubmitableViewModel<INoticeForm> {

    notice_id: number | null = $state(null);
    organization_name: string | null = $state(null);
    type_options: Option[] = $state([]);
    organization_options: Option[] = $state([]);
    trooper_stamps: ITrooperStamps | null = $state(null);

    constructor(pageData: NoticeFormPageData) {
        super();

        this.notice_id = pageData.notice.id;
        this.organization_name = pageData.notice.organization_name;
        this.type_options = pageData.type_options;
        this.organization_options = pageData.organization_options ?? [];
        this.trooper_stamps = pageData.notice.trooper_stamps ?? null;
        this.form = generateForm(pageData.notice);
    }

    get is_update(): boolean {
        return this.notice_id !== null;
    }

    /** Only administrators get the "Everyone" choice (no organization). */
    get can_post_to_everyone(): boolean {
        return this.organization_options.some((option) => option.value === null);
    }

    get cancel_url(): string {
        return getRoute("admin.notices.list", { organization_id: this.form.organization_id ?? undefined });
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = this.is_update
            ? getRoute("admin.notices.update", { notice: this.notice_id! })
            : getRoute("admin.notices.create");

        this.form.post(url);
    };
}
