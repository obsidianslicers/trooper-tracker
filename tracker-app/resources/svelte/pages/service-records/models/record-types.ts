/** Who the record is for (ServiceRecordTrooperResource). */
export type RecordTrooper = {
    id: number;
    display_name: string;
    rank_title: string;
    rank_theme: string;
    is_departed: boolean;
    is_handler: boolean;
    is_self: boolean;
    edit_url: string | null;
    missing_credit_url: string | null;
};

/** The left column (ServiceSummaryResource). Numbers come formatted. */
export type ServiceSummary = {
    total_shifts: string;
    total_hours: string;
    theme: string;
    clubs: { name: string; troops: string }[];
    rank: number | string | null;
    direct_funds: string;
    indirect_funds: string;
    total_donated: string | null;
    donation_months: number | null;
    milestones: { description: string; icon: string; is_501st: boolean }[];
    donor_image_url: string | null;
};

/** A pill beside a past shift that added no troop, with why on hover. */
export type ShiftFlag = { label: string; hint: string };

type ShiftBase = { id: number; event_name: string; event_url: string; date: string; time: string };

/** ServiceRecordShiftResource, upcoming. */
export type UpcomingShift = ShiftBase & {
    logo_url: string;
    costume: string | null;
    costume_organizations: string | null;
    is_handler: boolean;
};

/** ServiceRecordShiftResource, recent (History). */
export type RecentShift = ShiftBase & {
    logo_url: string;
    costume: string | null;
    is_handler: boolean;
    credited_to: string[];
    not_counted: ShiftFlag | null;
    same_day_repeat: ShiftFlag | null;
};

/** ServiceRecordShiftResource, waiting for "did you make it?". */
export type PendingShift = ShiftBase & { made_it_url: string; missed_it_url: string };

export type TrooperAward = { id: number; name: string; date: string | null };

export type TrooperOrganization = {
    id: number;
    name: string;
    logo_url: string;
    identifier_label: string;
    identifier: string | null;
    assignment: string | null;
};

export type TrooperCostume = { id: number; name: string; image_urls: string[]; organizations: string };

export type TaggedUpload = { id: number; small_url: string; large_url: string };

export type DonationRow = { id: number; date: string | null; amount: string };

/** The service record page (TrooperServiceRecordPageData). */
export type ServiceRecordPageData = {
    trooper: RecordTrooper;
    summary: ServiceSummary;
    upcoming_shifts: UpcomingShift[];
    recent_shifts: RecentShift[];
    pending_shifts: PendingShift[];
    awards: TrooperAward[];
    organizations: TrooperOrganization[];
    costumes: TrooperCostume[];
    tagged_uploads: TaggedUpload[];
    donations: DonationRow[] | null;
    donations_total: string | null;
    /** Null when the viewer may not see them (another member's record). */
    credit_requests: CreditRequest[] | null;
    record_slots: Record<string, string>;
};

/** A request for troop credit on the Credit Requests tab (CreditRequestResource). */
export type CreditRequest = {
    id: number;
    event_name: string;
    troop_date: string | null;
    requested_on: string | null;
    status: "awaiting_coordinator" | "awaiting_moderator" | "approved" | "denied";
    decided_on: string | null;
    url: string;
};
