<script lang="ts">
    import type { PageLink } from "$lib/domains/types.svelte";
    import { t } from "$lib/i18n";

    interface Props {
        /** Laravel's paginator links (a resource collection's `meta.links`). */
        links: PageLink[];
    }

    let { links }: Props = $props();
</script>

{#if links.length > 3}
    <nav aria-label={t("Pages")}>
        <ul class="pagination mb-0">
            {#each links as link, i (i)}
                <li class={["page-item", link.active && "active", !link.url && "disabled"]}>
                    {#if link.url && !link.active}
                        <a class="page-link" href={link.url}>{@html link.label}</a>
                    {:else}
                        <span class="page-link" aria-current={link.active ? "page" : undefined}>
                            {@html link.label}
                        </span>
                    {/if}
                </li>
            {/each}
        </ul>
    </nav>
{/if}
