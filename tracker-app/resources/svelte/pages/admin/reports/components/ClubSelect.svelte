<!--
    The club a report covers; empty means every club the reader moderates.
-->
<script lang="ts">
    import type { Option } from "$lib/domains/types.svelte";
    import { t } from "$lib/i18n";

    interface Props {
        value: number | null;
        options: Option[];
    }
    let { value = $bindable(), options }: Props = $props();

    const id = "id-" + crypto.randomUUID();
</script>

<div class="form-floating">
    <select {id} name="organization_id" class="form-select" bind:value>
        <option value={null}>-- {t("All Clubs")} --</option>
        {#each options as option (option.value)}
            <option value={option.value}>{option.label}</option>
        {/each}
    </select>
    <label for={id}>{t("Club")}</label>
</div>
