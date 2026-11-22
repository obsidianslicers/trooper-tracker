<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";
    import YesNoIcon from "./YesNoIcon.svelte";

    let { quotas }: { quotas: EventDetails["quotas"] } = $props();

    function clubList(names: string[]): string {
        if (names.length <= 1) {
            return names.join("");
        }

        return names.slice(0, -1).join(", ") + " " + t("or") + " " + names[names.length - 1];
    }
</script>

<SectionTitle>{t("Trooper Quotas")}</SectionTitle>
{#if quotas.is_unlimited}
    <!-- the per-club list would only repeat "a lot / a lot" -->
    <p class="mb-1">{t("No limits")}</p>
    {#if quotas.closed_names.length > 0}
        <p class="small text-muted">{t("Not open to :clubs", { clubs: clubList(quotas.closed_names) })}</p>
    {/if}
{:else}
    <ul class="list-group list-group-flush">
        <li class="list-group-item fw-bold">
            {t("Organization")}
            <span class="float-end">
                <span class="d-none d-md-inline">{t("Troopers / Handlers")}</span>
                <span class="d-inline d-md-none">{t("Trp / Hnd")}</span>
            </span>
        </li>
        {#each quotas.clubs as club (club.id)}
            <li class="list-group-item bg-transparent border-bottom">
                <YesNoIcon value={club.can_attend} />
                {club.name}
                {#if club.can_attend}
                    <span class="float-end">
                        {club.troopers_allowed ?? t("a lot")} / {club.handlers_allowed ?? t("a lot")}
                    </span>
                {/if}
            </li>
        {/each}
    </ul>
{/if}
