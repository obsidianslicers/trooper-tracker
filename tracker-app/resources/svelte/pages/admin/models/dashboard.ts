export type DashboardCounts = {
    not_approved: number;
    pending_join_requests: number;
    pending_costumes: number;
    troops_awaiting_verification: number;
    pending_permission_requests: number;
};

export type DashboardCardLink = { key: string; url: string };

export type CommandStaffDashboardPageData = {
    trooper_id: number;
    counts: DashboardCounts;
    system_check_problems: number;
    cards: DashboardCardLink[];
    /** Cards plugins add, rendered on the server. */
    plugin_cards: string;
};

export type SystemCheckRow = {
    label: string;
    detail: string | null;
    status: string;
    badge: string;
    icon: string;
};

export type SystemCheckPageData = {
    groups: { name: string; results: SystemCheckRow[] }[];
    log_entries: LogEntryRow[];
    has_more_errors: boolean;
};

export type LogEntryRow = {
    level: string;
    badge: string;
    logged_at: string;
    summary: string;
    message: string;
};
