<script lang="ts">
    import { t } from "$lib/i18n";
    import { route } from "ziggy-js";

    interface Props {
        term: string;
        type: string;
        types: Record<string, string>;
    }
    let { term, type, types }: Props = $props();
</script>

<form method="GET" action={route("search.all")} class="d-flex gap-2 allow-enter-keypress">
    <!-- svelte-ignore a11y_autofocus -->
    <input
        type="text"
        name="q"
        value={term}
        placeholder={t("Search troopers, events, TK IDs...")}
        aria-label={t("Search")}
        class="form-control"
        autofocus
    />
    <select name="type" class="form-select type" aria-label={t("Search in")} value={type}>
        {#each Object.entries(types) as [value, label] (value)}
            <option {value}>{label}</option>
        {/each}
    </select>
    <button type="submit" class="btn btn-primary text-nowrap">
        <i class="fa fa-fw fa-search"></i>
        {t("Search")}
    </button>
</form>

<style>
    .type {
        max-width: 160px;
    }
</style>
