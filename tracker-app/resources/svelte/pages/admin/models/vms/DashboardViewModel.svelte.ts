import { t } from "$lib/i18n";
import type { DashboardCounts } from "../dashboard";

/** A line on a card: plain text, or a count shown as waiting (warning) or clear (success). */
export type CardLine = { text: string; tone: "warning" | "success" | "danger" | null };

export type DashboardCard = { label: string; icon: string; lines: CardLine[]; danger: boolean };

export type DashboardAlert = {
    key: string;
    text: string;
    count: number;
    href: string | null;
};

/**
 * The Command Staff dashboard: the "waiting for you" alerts, which a reader can dismiss until the
 * count grows, and the text on each tool's card.
 */
export class DashboardViewModel {
    dismissed: Record<string, number> = $state({});

    constructor(
        private readonly counts: DashboardCounts,
        private readonly system_check_problems: number,
        private readonly trooper_id: number,
        private readonly permission_approvals_url: string,
    ) {
        for (const alert of this.allAlerts()) {
            this.dismissed[alert.key] = this.read(alert.key);
        }
    }

    get alerts(): DashboardAlert[] {
        return this.allAlerts().filter((alert) => alert.count > (this.dismissed[alert.key] ?? 0));
    }

    dismiss = (alert: DashboardAlert): void => {
        this.dismissed[alert.key] = alert.count;

        try {
            localStorage.setItem(this.storageKey(alert.key), JSON.stringify(alert.count));
        } catch {
            // private browsing: the alert stays hidden until the page reloads
        }
    };

    card(key: string): DashboardCard {
        const c = this.counts;
        const text = (value: string): CardLine => ({ text: value, tone: null });
        const count = (n: number, waiting: string, clear: string): CardLine =>
            n > 0 ? { text: waiting, tone: "warning" } : { text: clear, tone: "success" };
        const requests = (n: number): CardLine =>
            count(n, t(":count pending request|:count pending requests", {}, n), t("No pending requests"));

        const cards: Record<string, [string, string, CardLine[]]> = {
            "trooper-approvals": ["Trooper Approvals", "fa-user-check", [
                count(c.not_approved, t(":count awaiting approval", { count: c.not_approved }), t("All troopers approved")),
                requests(c.pending_join_requests),
            ]],
            "costume-approvals": ["Costume Approvals", "fa-user-astronaut", [
                count(
                    c.pending_costumes,
                    t(":count costume awaiting review|:count costumes awaiting review", {}, c.pending_costumes),
                    t("No costumes waiting"),
                ),
            ]],
            "troop-verifications": ["Troop Verifications", "fa-user-clock", [
                count(
                    c.troops_awaiting_verification,
                    t(
                        ":count troop awaiting verification|:count troops awaiting verification",
                        {},
                        c.troops_awaiting_verification,
                    ),
                    t("No troops waiting"),
                ),
            ]],
            troopers: ["Troopers", "fa-users-gear", [text(t("Manage Trooper's profile, authority, and memberships."))]],
            "missing-credit": ["Missing Credit", "fa-triangle-exclamation", [text(t('Fix shifts missing a "Credited To" club'))]],
            "request-permissions": ["Request Permissions", "fa-user-shield", [
                text(t("Ask an administrator to change a trooper's role or forum groups.")),
            ]],
            "permission-approvals": ["Permission Approvals", "fa-user-lock", [requests(c.pending_permission_requests)]],
            "merge-troopers": ["Merge Troopers", "fa-user-group", [text(t("Merge existing troopers into a single profile."))]],
            notices: ["Notices to the Troops", "fa-message", [text(t("Manage Site Messages"))]],
            events: ["Events", "fa-calendar-days", [text(t("Create, Update, and Manage Events"))]],
            "event-types": ["Event Types", "fa-tags", [
                text(t("Order event types, describe them, and choose which are non-troop-credit")),
            ]],
            awards: ["Awards", "fa-award", [text(t("Create, Update Awards, as well as assign them to troopers"))]],
            costumes: ["Costumes", "fa-wrench", [text(t("Create, Update, and Manage Costumes"))]],
            "merge-costumes": ["Merge Costumes", "fa-object-group", [
                text(t("Merge a duplicate costume into the one you want to keep.")),
            ]],
            organizations: ["Organizations", "fa-wrench", [text(t("Create, Update, and Manage Organizations, Regions, Units"))]],
            reports: ["Reports & Stats", "fa-file-lines", [text(t("View Reports & Statistics"))]],
            faq: ["FAQ & Help", "fa-question", [text(t("View Frequently Asked Questions and Help"))]],
            "system-check": ["System Check", "fa-heart-pulse", [
                text(t("Verify PHP, server, and integration settings")),
                ...(this.system_check_problems > 0
                    ? [{
                        text: t(
                            ":count check needs attention|:count checks need attention",
                            {},
                            this.system_check_problems,
                        ),
                        tone: "danger" as const,
                    }]
                    : []),
            ]],
            plugins: ["Plugins", "fa-puzzle-piece", [text(t("Enable, Disable, and Configure Plugins"))]],
        };

        const [label, icon, lines] = cards[key] ?? [key, "fa-circle", []];

        return {
            label: t(label),
            icon,
            lines,
            danger: key === "system-check" && this.system_check_problems > 0,
        };
    }

    private allAlerts(): DashboardAlert[] {
        const c = this.counts;

        return [
            {
                key: "approvals",
                count: c.not_approved,
                text: c.not_approved === 1
                    ? t("There is 1 trooper ready for action!")
                    : t("There are :count troopers ready for action!", { count: c.not_approved }),
                href: null,
            },
            {
                key: "requests",
                count: c.pending_join_requests,
                text: c.pending_join_requests === 1
                    ? t("1 trooper has a pending request!")
                    : t(":count troopers have a pending request!", { count: c.pending_join_requests }),
                href: null,
            },
            {
                key: "permission_requests",
                count: c.pending_permission_requests,
                text: c.pending_permission_requests === 1
                    ? t("1 permission request is waiting for review!")
                    : t(":count permission requests are waiting for review!", {
                        count: c.pending_permission_requests,
                    }),
                href: this.permission_approvals_url,
            },
        ];
    }

    /** The keys the Blade dashboard used, so earlier dismissals still count. */
    private storageKey(key: string): string {
        return `admin_dismissed_${key}_${this.trooper_id}`;
    }

    private read(key: string): number {
        try {
            return Number(JSON.parse(localStorage.getItem(this.storageKey(key)) ?? "0")) || 0;
        } catch {
            return 0;
        }
    }
}
