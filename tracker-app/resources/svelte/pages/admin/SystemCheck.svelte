<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import type { SystemCheckPageData } from "./models/dashboard";

    const page = usePage<SystemCheckPageData>();

    pageState.title = t("System Check");

    let open: Record<number, boolean> = $state({});
</script>

<div class="mt-2">
    <p class="text-muted">
        {t("Verifies PHP requirements, server settings, and integrations needed for this install to run correctly.")}
    </p>

    {#each page.props.groups as group (group.name)}
        <div class="card mb-3">
            <div class="card-header">{group.name}</div>
            <ul class="list-group list-group-flush">
                {#each group.results as result, i (i)}
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            {result.label}
                            {#if result.detail}
                                <br />
                                <small class="text-muted">{result.detail}</small>
                            {/if}
                        </span>
                        <span class="badge {result.badge}">
                            <i class="fa fa-fw fa-solid {result.icon}"></i>
                            {result.status}
                        </span>
                    </li>
                {/each}
            </ul>
        </div>
    {/each}

    <div class="card mb-3">
        <div class="card-header">{t("Errors (last 30 days)")}</div>
        {#if page.props.log_entries.length === 0}
            <div class="card-body text-muted">
                {t("No error-level log entries in the last 30 days.")}
            </div>
        {:else}
            <ul class="list-group list-group-flush">
                {#each page.props.log_entries as entry, i (i)}
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge {entry.badge} me-2">{entry.level}</span>
                            <small class="text-muted ms-auto">{entry.logged_at}</small>
                        </div>
                        <div class="mt-1">{entry.summary}</div>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary btn-secondary-action mt-1"
                            aria-expanded={open[i] ?? false}
                            onclick={() => (open[i] = !open[i])}
                        >
                            {t("View full error")}
                        </button>
                        {#if open[i]}
                            <pre class="bg-dark text-light p-2 rounded small mb-0 mt-2" style="white-space: pre-wrap;">{entry.message}</pre>
                        {/if}
                    </li>
                {/each}
            </ul>
        {/if}
    </div>
</div>
