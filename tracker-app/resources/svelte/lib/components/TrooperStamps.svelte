<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";

    interface Props {
        trooper_stamps: null | {
            created_id: number;
            created_at: string;
            created_by: string;
            updated_id: number;
            updated_at: string;
            updated_by: string;
            deleted_id: number;
            deleted_at: string;
            deleted_by: string;
        };
    }

    let { trooper_stamps }: Props = $props();
</script>

{#snippet who(id: number, name: string, at: string)}
    {#if name}
        {t("by")}
        <a href={getRoute("admin.troopers.profile", { trooper: id })}>
            {name}
        </a>
    {/if}
    {at}
{/snippet}

<hr />
{#if trooper_stamps}
    <div class="row">
        <div class="col-12 text-end">
            {#if trooper_stamps.deleted_at}
                <span class="text-muted">
                    {t("soft deleted")}
                    {@render who(
                        trooper_stamps.deleted_id,
                        trooper_stamps.deleted_by,
                        trooper_stamps.deleted_at,
                    )}
                </span>
            {:else if trooper_stamps.created_at == trooper_stamps.updated_at}
                <span class="text-muted">
                    {t("created")}
                    {@render who(
                        trooper_stamps.created_id,
                        trooper_stamps.created_by,
                        trooper_stamps.created_at,
                    )}
                </span>
            {:else}
                <span class="text-muted">
                    {t("updated")}
                    {@render who(
                        trooper_stamps.updated_id,
                        trooper_stamps.updated_by,
                        trooper_stamps.updated_at,
                    )}
                </span>
            {/if}
        </div>
    </div>
{/if}
