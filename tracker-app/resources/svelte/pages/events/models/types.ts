export type EventView = "list" | "calendar" | "map" | "closed" | "cancelled";

export type EventShift = {
    id: number;
    url: string;
    date: string;
    starts: string;
    ends: string;
    troopers: number;
    handlers: number;
    guests: number;
    my_status: string | null;
    my_status_label: string | null;
};

export type EventOrganization = {
    id: number;
    name: string;
    can_attend: boolean;
};

export type EventSummary = {
    id: number;
    name: string;
    url: string;
    venue_address: string | null;
    organization_id: number | null;
    organization_name: string | null;
    logo_url: string;
    month_key: string;
    month_label: string;
    weekday: string;
    day: string;
    month: string;
    date_label: string;
    is_at_risk: boolean;
    is_locked: boolean;
    not_counted_notice: string | null;
    troopers_allowed: number | null;
    shifts: EventShift[];
    organizations: EventOrganization[];
};

export type HostingOrganization = {
    id: number;
    name: string;
    parent_id: number | null;
    depth: number;
};

export type UnreadNotice = {
    id: number;
    type: 'info' | 'success' | 'warning' | 'danger';
    heading: string;
    title: string;
    html: string;
};

export type Notices = {
    count: number;
    notice: UnreadNotice | null;
};

export type SupportGoal = {
    goal: number;
    progress: number;
    message: string;
    url: string | null;
};

export type EventMonth = {
    key: string;
    label: string;
    events: EventSummary[];
};

export type ListEventsPageData = {
    events: EventSummary[];
    hosting_organizations: HostingOrganization[];
    notices: Notices;
    has_map: boolean;
    support?: SupportGoal | null;
};

export type PastEvent = {
    id: number;
    name: string;
    url: string;
    date_label: string | null;
    organization_id: number | null;
    organization_name: string | null;
    logo_url: string;
    shifts: number;
    unique_troopers: number;
    total_troopers: number;
    direct_funds: number;
    indirect_funds: number;
};

export type PastEventTotals = {
    shifts: number;
    unique_troopers: number;
    total_troopers: number;
    direct_funds: number;
    indirect_funds: number;
};

export type Pagination = {
    current_page: number;
    last_page: number;
    total: number;
    prev_url: string | null;
    next_url: string | null;
};

export type PastEventsPageData = {
    status: "closed" | "cancelled";
    lookback_days: number | null;
    events: PastEvent[];
    totals: PastEventTotals;
    pagination: Pagination | null;
    hosting_organizations: HostingOrganization[];
    has_map: boolean;
};

export type CalendarDay = {
    date: string;
    day: string;
    weekday: string;
    in_month: boolean;
};

export type CalendarMonth = {
    key: string;
    label: string;
    weeks: CalendarDay[][];
};

export type CalendarEvent = {
    id: number;
    name: string;
    url: string;
    date: string;
    starts: string;
    venue_address: string | null;
    organization_id: number | null;
    logo_url: string;
    not_counted_notice: string | null;
    is_at_risk: boolean;
    is_mine: boolean;
};

export type CalendarPageData = {
    months: CalendarMonth[];
    weekdays: string[];
    today: string;
    events: CalendarEvent[];
    holidays: Record<string, string[]>;
    hosting_organizations: HostingOrganization[];
    has_map: boolean;
};

export type MapEvent = {
    name: string;
    url: string;
    date_range: string;
    venue_address: string | null;
    organization_id: number | null;
    lat: number;
    lng: number;
};

export type MapPageData = {
    events: MapEvent[];
    hosting_organizations: HostingOrganization[];
    has_map: boolean;
};
