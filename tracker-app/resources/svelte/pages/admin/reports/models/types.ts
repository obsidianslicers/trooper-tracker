/** Numbers arrive formatted for the reader's language; "-" means none. */

export type LeaderboardRow = {
    name: string;
    events_completed: string;
    troopers_attended: string;
    direct_funds_raised: string;
    indirect_funds_raised: string;
    total_funds_raised: string;
};

export type ReportsDashboardPageData = {
    days: number;
    lookbacks: number[];
    personnel: {
        active_count: string;
        trooper_count: string;
        handler_count: string;
        new_enlistments: string;
        ghost_count: string;
        attendance_count: string;
        attrition_risk: string;
    };
    fulfillment_rate: string;
    impact: { total_credits: string; volunteer_hours: string; internal_donations: string };
    awards_issued: string;
    photo_activity: string;
    leaderboard: LeaderboardRow[];
    /** The "Attended" status as the reader's language names it. */
    attended: string;
};

export type StatusChangeRow = {
    id: number;
    changed_at: string;
    event_name: string;
    event_url: string;
    shift: string;
    trooper_name: string;
    trooper_url: string;
    updated_by: string;
};

export type StatusChangeLogPageData = { caption: string; changes: StatusChangeRow[] };

export type InactiveTrooperRow = {
    id: number;
    display_name: string;
    profile_url: string;
    last_seen: string;
};

export type TroopersWithoutActivityPageData = { caption: string; troopers: InactiveTrooperRow[] };

export type EventTypeCountRow = {
    id: number;
    name: string;
    events_url: string;
    count: string;
    unique_trooper_count: string;
    total_trooper_count: string;
};

export type EventTypeCountPageData = {
    days: number;
    event_types: EventTypeCountRow[];
    totals: { count: string; unique_trooper_count: string; total_trooper_count: string };
};

export type EventSummaryRow = {
    id: number;
    name: string;
    url: string;
    logo_url: string;
    shifts: string;
    unique_trooper_count: string;
    total_trooper_count: string;
    direct: string;
    indirect: string;
};

export type EventSummaryPageData = {
    days: number;
    events: EventSummaryRow[];
    totals: {
        shifts: string;
        unique_trooper_count: string;
        total_trooper_count: string;
        direct: string;
        indirect: string;
    };
};
