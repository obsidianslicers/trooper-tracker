export type PickOption = { value: string | number; label: string };

export type SignUpStatus = { value: string; label: string; color: string };

export type ShareLink = { provider: string; url: string; icon: string };

export type CalendarLinks = { google: string; yahoo: string; outlook: string; ics: string };

export type EventDetails = {
    name: string;
    header: { color: "primary" | "danger" | "secondary"; logo_url: string | null };
    organization_name: string | null;
    status_label: string;
    when: string;
    shift_count: number;
    not_counted_notice: string | null;
    one_troop_per_day_notice: string | null;
    venue: { name: string | null; address: string | null; map_url: string | null; website: string | null };
    contact: { name: string; phone: string | null; email: string | null } | null;
    coordinator: { name: string; url: string } | null;
    mission_brief: {
        html: string;
        is_required: boolean;
        is_acknowledged: boolean;
        acknowledge_url: string;
    } | null;
    logistics: {
        secure_staging_area: boolean;
        allow_blasters: boolean;
        allow_props: boolean;
        parking_available: boolean;
        accessible: boolean;
        notes_html: string | null;
    };
    limits: {
        expected_attendees: number | null;
        shifts_allowed: number | null;
        troopers_allowed: number | null;
        handlers_allowed: number | null;
    };
    quotas: {
        is_unlimited: boolean;
        closed_names: string[];
        clubs: {
            id: number;
            name: string;
            can_attend: boolean;
            troopers_allowed: number | null;
            handlers_allowed: number | null;
        }[];
    };
    requested_characters: { number: number | null; types: string | null };
    charity: {
        id: number;
        when: string;
        name: string | null;
        direct_funds: string;
        indirect_funds: string;
        hours: number | null;
        notes: string | null;
    }[];
    calendar: CalendarLinks;
    shares: ShareLink[];
    edit_url: string | null;
    copy_url: string | null;
    uploads_default_to_instructional: boolean;
};

export type CostumePicker = { value: string; backup_value: string; options: PickOption[] };

export type RowEditor = {
    stations: { value: number | null; options: PickOption[] } | null;
    organizations: { value: number | null; options: PickOption[] } | null;
    costumes: CostumePicker | null;
    requires_costume: boolean;
    plans: { value: string; options: PickOption[] } | null;
    can_cancel: boolean;
    cancel_confirm: string;
};

export type TrooperRow = {
    id: number;
    number: number | null;
    trooper: { name: string; legal_name: string; url: string };
    is_mine: boolean;
    needs_verification: boolean;
    added_by: string | null;
    status: SignUpStatus;
    costume: {
        name: string | null;
        organizations: string | null;
        is_handler: boolean;
        is_without_costume: boolean;
        backup: string | null;
    } | null;
    station: string | null;
    approved_by: string | null;
    actions: {
        update_url: string;
        approve: boolean;
        reject: boolean;
        needs_costume_to_approve: SignUpStatus | null;
        sign_up_again: "reinstate" | "going" | null;
        /** null once answered: the row then shows the answer as its status */
        attendance: { url: string } | null;
    };
    editor: RowEditor | null;
    opens_itself: boolean;
};

export type GuestRow = {
    id: number;
    name: string;
    added_by: string | null;
    status: SignUpStatus;
    update_url: string;
    approve: boolean;
    reject: boolean;
    sign_up_again: string | null;
    can_cancel: boolean;
    cancel_confirm: string;
    approved_by: string | null;
};

export type SignUpChoices = {
    organizations: PickOption[];
    stations: PickOption[];
    requires_costume: boolean;
    costumes: PickOption[];
    default_costume: string;
};

export type SignUpForm = SignUpChoices & {
    needs_mission_brief: false;
    url: string;
    is_full: boolean;
    hint: string | null;
    plans: PickOption[];
};

export type FriendForm = SignUpChoices & {
    trooper: { id: number; name: string };
};

export type DisplayShift = {
    id: number;
    label: string;
    is_open: boolean;
    is_mine: boolean;
    status_label: string | null;
    fullness: {
        spots_left: number | null;
        limit: number | null;
        going: number;
        tentative: number;
        stand_by: number;
    } | null;
    stations: { id: number; name: string; going: number; limit: number | null }[];
    same_day: { is_warning: boolean; times: string; day: string } | null;
    going: TrooperRow[];
    stand_by: TrooperRow[];
    cancelled: TrooperRow[];
    guests: GuestRow[];
    sign_up: SignUpForm | { needs_mission_brief: true } | null;
    links: {
        friend_form_url: string;
        can_add_friend: boolean;
        can_add_guest: boolean;
        guest_url: string;
        signup_url: string;
        calendar: CalendarLinks | null;
        csv_url: string | null;
    };
};

export type MySignUp = {
    id: number;
    shift_id: number;
    time: string | null;
    status: SignUpStatus;
    is_active: boolean;
    costume: string | null;
    is_without_costume: boolean;
    is_handler: boolean;
    requires_costume: boolean;
    backup: string | null;
    can_change: boolean;
};

export type MySignUps = {
    has_open_shift: boolean;
    open_shift_ids: number[];
    sign_ups: MySignUp[];
};

export type DisplayUpload = {
    id: number;
    small_url: string;
    large_url: string;
    uploaded_by: string | null;
    is_tagged: boolean;
    tag_url: string | null;
    move_url: string | null;
    shares: ShareLink[];
};

export type DisplayUploads = {
    instructional: DisplayUpload[];
    mission_review: DisplayUpload[];
    upload_url: string;
};

export type DisplayTools = {
    roster_csv_url: string | null;
    share_roster_url: string | null;
    broadcast_url: string | null;
    broadcast_status: string;
    watch_url: string;
};

export type SignUpTourData = {
    auto_start: boolean;
    storage_key: string;
    requires_costume: boolean;
    has_plans: boolean;
    plans: string[];
    going_label: string;
    is_brief_pending: boolean;
};

export type EventDisplayPageData = {
    event: EventDetails;
    shifts: DisplayShift[];
    my_sign_ups: MySignUps;
    uploads: DisplayUploads;
    is_watching: boolean;
    tools: DisplayTools;
    tour: SignUpTourData;
    event_slots: Record<string, string>;
};
