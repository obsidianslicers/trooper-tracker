<script lang="ts">
    import { locale, t } from "$lib/i18n";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";

    let { limits }: { limits: EventDetails["limits"] } = $props();

    const number = (value: number | null, fallback: string) =>
        value === null ? fallback : new Intl.NumberFormat(locale()).format(value);

    const rows = $derived([
        { label: t("Expected Attendees"), value: number(limits.expected_attendees, t("a lot")) },
        { label: t("Maximum Shifts Allowed"), value: number(limits.shifts_allowed, t("all")) },
        { label: t("Troopers Allowed"), value: number(limits.troopers_allowed, t("a lot")) },
        { label: t("Handlers Allowed"), value: number(limits.handlers_allowed, t("a lot")) },
    ]);
</script>

<SectionTitle>{t("Deployment Limits")}</SectionTitle>
<ul class="list-group list-group-flush">
    {#each rows as row (row.label)}
        <li class="list-group-item bg-transparent border-bottom">
            {row.label}
            <span class="float-end">{row.value}</span>
        </li>
    {/each}
</ul>
