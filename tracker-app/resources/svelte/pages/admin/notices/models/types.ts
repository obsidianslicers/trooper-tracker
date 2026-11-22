import type { ITrooperStamps, Option, PaginatedResource } from "$lib/domains/types.svelte";

export type NoticeScope = "active" | "past" | "future";

export type NoticeRow = {
    id: number;
    title: string;
    type: string;
    organization: { id: number; name: string } | null;
    logo_url: string;
    can_update: boolean;
};

export type ListPageData = {
    notices: PaginatedResource<NoticeRow>;
    scope: NoticeScope;
    organization: { id: number; name: string } | null;
};

export interface INoticeForm {
    organization_id: number | null;
    type: string;
    title: string;
    starts_at: string | null;
    ends_at: string | null;
    message: string;
}

export type NoticeFormPageData = {
    notice: INoticeForm & {
        id: number | null;
        organization_name: string | null;
        trooper_stamps?: ITrooperStamps;
    };
    type_options: Option[];
    organization_options?: Option[];
};
