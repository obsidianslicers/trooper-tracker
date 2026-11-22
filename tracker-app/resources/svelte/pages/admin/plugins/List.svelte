<script lang="ts">
    import { t } from "$lib/i18n";
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import PluginRow from "./components/PluginRow.svelte";
    import { ListViewModel, type ListPageData } from "./models";

    const page = usePage<ListPageData>();

    pageState.title = t("Plugins");

    let vm = new ListViewModel(page.props);
</script>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>{t("Plugin")}</th>
                <th>{t("Version")}</th>
                <th>{t("Source")}</th>
                <th>{t("Status")}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {#each vm.plugins as plugin (plugin.slug)}
                <PluginRow {vm} {plugin} />
            {:else}
                <tr>
                    <td colspan="5" class="text-muted">
                        {t("No plugins found. Upload one below or add it to the plugins folder.")}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

{#if page.props.missing.length > 0}
    <div class="card mt-4">
        <div class="card-header">{t("Missing plugins")}</div>
        <div class="card-body">
            <p class="text-muted small">
                {t("These plugins were set up before, but their files are gone from the server. Their settings and data are kept. Put the files back and the next deploy restores them as they were.")}
            </p>
            <ul class="list-group">
                {#each page.props.missing as record (record.slug)}
                    <li class="list-group-item">
                        <strong>{record.slug}</strong>
                        <span class="text-muted small">{record.details}</span>
                        {#if record.last_error}
                            <div class="small text-danger">{record.last_error}</div>
                        {/if}
                    </li>
                {/each}
            </ul>
        </div>
    </div>
{/if}

<div class="card mt-4">
    <div class="card-header">{t("Install a plugin")}</div>
    <div class="card-body">
        {#if page.props.uploads.is_enabled}
            <form onsubmit={vm.install} novalidate>
                <p class="text-muted small">
                    {t("Upload a plugin as a .zip file (up to :size MB) with its plugin.json at the top. It is installed disabled. Composer packages it needs are installed by the next deploy. A plugin runs code on the server once enabled, so only install plugins you trust.", { size: page.props.uploads.max_megabytes })}
                </p>
                <div class="input-group">
                    <input
                        type="file"
                        name="archive"
                        class={["form-control", vm.errors.archive && "is-invalid"]}
                        accept=".zip,application/zip"
                        aria-label={t("Plugin .zip file")}
                        onchange={(e) => vm.pickArchive(e.currentTarget.files?.[0])}
                    />
                    <button type="submit" class="btn btn-primary" disabled={vm.submitting || !vm.form.archive}>
                        {#if vm.submitting}
                            <i class="fa fa-fw fa-spinner fa-spin"></i>
                        {/if}
                        {t("Install")}
                    </button>
                </div>
                <InputError errors={vm.errors.archive} />
            </form>
        {:else}
            <p class="text-muted small mb-0">
                {t("Installing plugins from here is turned off. The server sets TRACKER_PLUGINS_ALLOW_UPLOADS=false. Remove that to allow it, or copy plugins into storage/app/plugins on the server.")}
            </p>
        {/if}
    </div>
</div>

<ConfirmationModal
    show={vm.is_confirming}
    message={vm.pending?.message}
    confirmLabel={vm.pending?.label}
    danger={vm.pending?.danger ?? false}
    onConfirm={vm.confirm}
    onCancel={vm.cancel}
/>
