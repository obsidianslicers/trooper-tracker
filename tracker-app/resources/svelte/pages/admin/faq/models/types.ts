
export interface ISectionForm {
    label: string;
    icon: string;
}

export interface IItemForm {
    section_id: number | null;
    title: string;
    description: string | null;
    video_url: string | null;
}
