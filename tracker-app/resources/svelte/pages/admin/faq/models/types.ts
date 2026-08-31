import type { Option } from "$lib/domains/types.svelte";

export type FaqSectionOption = Option;

export interface ISectionForm {
    label: string;
    icon: string;
}

export type FaqItem = {
    id: number;
    section_id: number;
    title: string;
    description: string | null;
    video_url: string | null;
    sort_order: number;
    faq_section?: { id: number; label: string; icon: string } | null;
};

export type FaqFormData = {
    section_id: number | null;
    title: string;
    description: string | null;
    video_url: string | null;
};

export type FaqSectionFormData = {
    label: string;
    icon: string;
};
