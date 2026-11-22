<!-- The pickers before Confirm, for your own Sign Up or a friend's. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { SignUpFormViewModel } from "../../models";

    let { form, key }: { form: SignUpFormViewModel; key: string } = $props();

    const choices = $derived(form.choices);
</script>

{#if choices.organizations.length > 1}
    <select class="form-select form-select-sm" aria-label={t("Organization")} bind:value={form.organization_id}>
        {#if !form.for_friend}
            <option value="">{t("-- Select Organization --")}</option>
        {/if}
        {#each choices.organizations as option (option.value)}
            <option value={String(option.value)}>{option.label}</option>
        {/each}
    </select>
{:else if form.only_organization}
    <span class="small text-muted w-100">
        {t("Trooping as :organization", { organization: form.only_organization.label })}
    </span>
{/if}

{#if choices.stations.length > 0}
    <select class="form-select form-select-sm" aria-label={t("Station")} bind:value={form.station_id}>
        <option value="">{t("-- Select Station --")}</option>
        {#each choices.stations as option (option.value)}
            <option value={String(option.value)}>{option.label}</option>
        {/each}
    </select>
{/if}

{#if choices.requires_costume}
    <select id="costume-{key}" class="form-select form-select-sm" aria-label={t("Costume")} bind:value={form.costume_id}>
        <option value="">{t("-- Select Costume --")}</option>
        {#each choices.costumes as option (option.value)}
            <option value={String(option.value)}>{option.label}</option>
        {/each}
        <option value="none">{t("Attending without a costume")}</option>
    </select>
{/if}

{#if (choices.plans?.length ?? 0) > 1}
    <select class="form-select form-select-sm shift-signup-primary__status" aria-label={t("Your plans")}
            bind:value={form.status}>
        {#each choices.plans ?? [] as option (option.value)}
            <option value={String(option.value)}>{option.label}</option>
        {/each}
    </select>
{/if}

{#if !form.for_friend && choices.requires_costume && choices.costumes.length > 0}
    <!-- optional: a button until they want one, then the picker -->
    <div class="shift-signup__backup">
        {#if form.has_backup}
            <!-- svelte-ignore a11y_autofocus -->
            <select class="form-select form-select-sm" aria-label={t("Backup costume (optional)")} autofocus
                    bind:value={form.backup_costume_id}>
                <option value="">{t("-- No backup costume --")}</option>
                {#each choices.costumes as option (option.value)}
                    <option value={String(option.value)}>{t("Backup: :costume", { costume: option.label })}</option>
                {/each}
            </select>
        {:else}
            <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" onclick={form.addBackup}>
                {t("Add a backup costume")}
            </button>
        {/if}
    </div>
{/if}
