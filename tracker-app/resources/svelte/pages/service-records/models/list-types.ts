import type { PaginatedResource } from "$lib/domains/types.svelte";

/** RecentAchievementResource. */
export type RecentAchievement = {
    id: number;
    trooper_name: string;
    trooper_url: string;
    description: string;
    icon: string;
    date: string;
};

/** RecentAwardResource. */
export type RecentAward = {
    id: number;
    award: string;
    organization: string;
    logo_url: string;
    trooper_name: string;
    trooper_url: string;
    date: string;
};

/** CommandStaffMemberResource. */
export type CommandStaffMember = {
    id: number;
    display_name: string;
    url: string;
    role: string;
    organizations: string;
};

/** GalleryPhotoResource. */
export type GalleryPhoto = {
    id: number;
    small_url: string;
    large_url: string;
    event_name: string;
    event_url: string;
    event_date: string | null;
    uploaded_by: string;
    tagged: string;
};

/** AchievementsPageData. */
export type AchievementsPageData = { days: number; achievements: RecentAchievement[] };

/** AwardsPageData. */
export type AwardsPageData = { days: number; awards: RecentAward[] };

/** CommandStaffPageData. */
export type CommandStaffPageData = { troopers: CommandStaffMember[] };

/** PhotoGalleryPageData. */
export type PhotoGalleryPageData = { photos: PaginatedResource<GalleryPhoto> };
