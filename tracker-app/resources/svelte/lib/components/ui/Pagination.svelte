<script lang="ts">
    import { paginationItems } from "$lib/components/models/PaginationItems";
    import type { PageLink } from "$lib/domains/types.svelte";
    import { t } from "$lib/i18n";

    interface Props {
        /** Laravel's paginator links (a resource collection's `meta.links`). */
        links: PageLink[];
    }

    let { links }: Props = $props();

    let items = $derived(paginationItems(links));

    const plainText = (html: string) => html.replace(/&[a-z]+;/gi, "").trim();
</script>

{#if links.length > 3}
    <nav aria-label={t("Pages")}>
        <ul class="pagination flex-wrap mb-0">
            {#each items as item, i (i)}
                {#if item.kind === "gap"}
                    <li
                        class={[
                            "page-item disabled",
                            item.wide && "d-none d-sm-block",
                            item.narrow && "d-sm-none",
                        ]}
                        aria-hidden={item.narrow ? "true" : undefined}
                    >
                        <span class="page-link">&hellip;</span>
                    </li>
                {:else}
                    {@const link = item.link}
                    <li
                        class={[
                            "page-item",
                            link.active && "active",
                            !link.url && "disabled",
                            item.wide && "d-none d-sm-block",
                        ]}
                    >
                        {#if link.url && !link.active}
                            <a
                                class="page-link"
                                href={link.url}
                                aria-label={item.edge ? plainText(link.label) : undefined}
                            >
                                {@render label(link.label, item.edge)}
                            </a>
                        {:else}
                            <span
                                class="page-link"
                                aria-current={link.active ? "page" : undefined}
                                aria-label={item.edge ? plainText(link.label) : undefined}
                            >
                                {@render label(link.label, item.edge)}
                            </span>
                        {/if}
                    </li>
                {/if}
            {/each}
        </ul>
    </nav>
{/if}

{#snippet label(html: string, edge: "previous" | "next" | null)}
    {#if edge}
        <span class="d-sm-none" aria-hidden="true">{edge === "previous" ? "‹" : "›"}</span>
        <span class="d-none d-sm-inline">{@html html}</span>
    {:else}
        {@html html}
    {/if}
{/snippet}
