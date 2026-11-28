/** A completed event on the Request Troop Credit page (TroopCorrectionEventResource). */
export type CorrectionEvent = {
    id: number;
    name: string;
    date: string | null;
    venue: string | null;
    shifts: { id: number; label: string }[];
    can_request: boolean;
};

/** Costumes the trooper can pick and the clubs each can credit (TroopCorrectionCreditOptions). */
export type CorrectionCreditOptions = {
    costumes: { id: number; name: string; club_ids: number[] }[];
    clubs: { id: number; name: string }[];
    no_costume_club_ids: number[];
};

export type RequestCreditPageData = {
    date: string | null;
    search: string | null;
    searched: boolean;
    events: CorrectionEvent[];
    event: CorrectionEvent | null;
    credit_options: CorrectionCreditOptions | null;
    no_match_html: string;
};

export type RequestCreditForm = {
    event_id: number | null;
    event_shift_id: number | null;
    costume_id: number | null;
    organization_ids: number[];
    details: string;
};

/** One request for troop credit as its coordinator sees it (TroopCorrectionReviewResource). */
export type TroopCorrectionReview = {
    id: number;
    trooper_name: string;
    event: { id: number; name: string };
    shift: string | null;
    costume: string | null;
    clubs: string[];
    details: string;
    status: "awaiting_coordinator" | "awaiting_moderator" | "approved" | "denied";
    can_answer: boolean;
};

export type TroopCorrectionPageData = {
    correction: TroopCorrectionReview;
};
