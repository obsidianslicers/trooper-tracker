import type { ITrooperStamps, Option, Smiley } from "$lib/domains/types.svelte";

export type EventOverviewForm = {
    organization_id: number | null;
    name: string;
    event_type_id: number | null;
    status: string;
    event_start: string | null;
    event_end: string | null;
    event_website: string | null;
    latitude: string | null;
    longitude: string | null;
    venue: string | null;
    venue_address: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    expected_attendees: string | null;
    requested_number_characters: string | null;
    requested_character_types: string | null;
    shifts_allowed: string | null;
    troopers_allowed: string | null;
    handlers_allowed: string | null;
    friends_allowed: string | null;
    guests_allowed: string | null;
    tentative_signups_allowed: boolean;
    secure_staging_area: boolean;
    parking_available: boolean;
    allow_blasters: boolean;
    allow_props: boolean;
    accessible: boolean;
    amenities: string | null;
    comments: string | null;
    require_mission_brief_ack: boolean;
    referred_by: string | null;
    /** "" (or null) means the event's creator. */
    coordinator_trooper_id: number | "" | null;
};

export type EventOrganizationLimit = {
    id: number;
    name: string;
    image_url: string;
    can_attend: boolean;
    troopers_allowed: string | null;
    handlers_allowed: string | null;
};

export type EventTypeOption = Option & {
    description: string;
    is_counted: boolean;
};

export type EventOverview = {
    form: EventOverviewForm;
    organizations: EventOrganizationLimit[];
    host_options: Option[];
    host_name: string | null;
    can_change_host: boolean;
    coordinator_options: Option[];
    coordinator_creator: string;
    event_types: EventTypeOption[];
    status_options: Option[];
    smilies: Smiley[];
    plugin_fields: string;
    display_url: string;
    trooper_stamps: ITrooperStamps | null;
};

export type EventStation = {
    id: number;
    name: string;
    troopers_allowed: string | null;
};

export type EventShift = {
    id: number;
    day: string;
    date: string;
    starts_at: string;
    ends_at: string;
    status: string;
    stations: EventStation[];
};

export type EventShiftCharity = {
    id: number;
    date: string;
    time: string;
    shift_hours: number;
    charity_name: string;
    charity_hours: string;
    charity_direct_funds: string;
    charity_indirect_funds: string;
    charity_notes: string;
};

export type CreditOption = { id: number; name: string };

export type RosterTrooper = {
    id: number;
    trooper_id: number;
    display_name: string;
    legal_name: string | null;
    profile_url: string;
    costume_id: number | null;
    costume_options: Option[];
    backup_costume: string | null;
    status: string;
    event_shift_station_id: number | null;
    org_options: CreditOption[];
    organization_ids: number[];
    approved_by: string | null;
    approved_at: string | null;
};

export type RosterGuest = {
    id: number;
    name: string;
    added_by: string | null;
    status: string;
    approved_by: string | null;
    approved_at: string | null;
};

export type RosterShift = {
    id: number;
    time_display: string;
    uses_stations: boolean;
    station_options: Option[];
    troopers: RosterTrooper[];
    guests: RosterGuest[];
};

export type EventRoster = {
    is_manual_selection: boolean;
    has_station_shifts: boolean;
    can_manage: boolean;
    status_options: Option[];
    guest_status_options: Option[];
    shifts: RosterShift[];
};

export type EventUpload = {
    id: number;
    small_url: string;
    uploaded_by: string | null;
    tagged: string[];
};

export type UpdateEventPageData = {
    event_id: number;
    name: string;
    status_label: string;
    is_active: boolean;
    is_draft: boolean;
    overview: EventOverview;
    shifts: EventShift[];
    charity: EventShiftCharity[];
    roster: EventRoster;
    uploads: EventUpload[];
    mission_review: EventUpload[];
    results?: EventPageResults;
};

/** A save answers with only the sections it changed. */
export type EventPageResults = Partial<Omit<UpdateEventPageData, "results">>;

export type AddTrooperChoices = {
    trooper_id: number;
    display_name: string;
    costume_options: Option[];
    organization_options: Option[];
    station_options: Option[];
};
