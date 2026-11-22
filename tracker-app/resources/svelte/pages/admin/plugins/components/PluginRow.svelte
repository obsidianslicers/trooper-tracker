<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import type { ListViewModel, PluginRow } from "../models";

    interface Props {
        vm: ListViewModel;
        plugin: PluginRow;
    }

    let { vm, plugin }: Props = $props();

    const busy = $derived(vm.working === plugin.slug);
</script>

<tr>
    <td>
        <strong>{plugin.name}</strong>
        <span class="text-muted small">({plugin.slug})</span>
        {#if plugin.description}
            <div class="small text-muted">{plugin.description}</div>
        {/if}
        {#if plugin.author}
            <div class="small text-muted">{plugin.author}</div>
        {/if}
        {#if plugin.capabilities}
            <div class="small text-muted">{plugin.capabilities}</div>
        {/if}
    </td>
    <td>
        {plugin.version}
        {#if plugin.pending_upgrade}
            <div
                class="small text-warning"
                title={t("Run php artisan plugins:reconcile to finish updating it.")}
            >
                {plugin.pending_upgrade}
            </div>
        {/if}
    </td>
    <td>{plugin.source}</td>
    <td>
        <span class="badge text-bg-{plugin.status.tone}" title={plugin.status.title}>
            {plugin.status.label}
        </span>
        {#if plugin.requirements}
            <div class={["small", plugin.requirements.is_problem ? "text-danger" : "text-muted"]}>
                {plugin.requirements.text}
            </div>
        {/if}
        {#if plugin.last_error}
            <div class="small text-danger">{plugin.last_error}</div>
        {/if}
    </td>
    <td class="text-end text-nowrap">
        {#if plugin.has_settings}
            <a
                href={getRoute("admin.plugins.settings", { slug: plugin.slug })}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="fa fa-fw fa-gear"></i>
                {t("Settings")}
            </a>
        {/if}
        {#if plugin.toggle === "disable"}
            <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                disabled={busy}
                onclick={() => vm.toggle(plugin)}
            >
                {t("Disable")}
            </button>
        {:else if plugin.toggle === "cancel"}
            <button
                type="button"
                class="btn btn-sm btn-link text-muted"
                disabled={busy}
                onclick={() => vm.toggle(plugin)}
            >
                {t("Cancel")}
            </button>
        {:else}
            <button
                type="button"
                class="btn btn-sm btn-outline-success"
                disabled={busy || !plugin.can_enable}
                onclick={() => vm.toggle(plugin)}
            >
                {t("Enable")}
            </button>
        {/if}
        {#if plugin.is_installed}
            <button
                type="button"
                class="btn btn-sm btn-danger"
                disabled={busy}
                onclick={() => vm.confirmUninstall(plugin)}
            >
                {t("Uninstall")}
            </button>
        {/if}
    </td>
</tr>
