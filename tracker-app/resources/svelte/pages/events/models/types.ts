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

export type OrganizationOption = {
    value: number;
    label: string;
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
    current: number;
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
    costume_organizations: OrganizationOption[];
    hosting_organizations: HostingOrganization[];
    notices: Notices;
    has_map: boolean;
    support?: SupportGoal | null;
};
