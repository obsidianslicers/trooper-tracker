export type PendingCostumeRow = {
    id: number;
    trooper: { id: number; name: string };
    costume: string;
    club: string;
    requested_at: string | null;
};

export type CostumeApprovalsPageData = {
    trooper_costumes: PendingCostumeRow[];
};

export type TroopVerificationRow = {
    id: number;
    trooper: { id: number; name: string };
    event: { id: number; name: string } | null;
    starts_at: string | null;
    costume: string;
    has_costume: boolean;
    clubs: { id: number; name: string }[];
};

export type TroopVerificationsPageData = {
    event_troopers: TroopVerificationRow[];
};
