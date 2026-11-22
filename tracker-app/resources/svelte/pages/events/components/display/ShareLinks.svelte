<script lang="ts">
    import type { ShareLink } from "../../models";

    let { links, classes = "btn-outline-secondary" }: { links: ShareLink[]; classes?: string } = $props();
</script>

<div class="btn-group" role="group">
    {#each links as link (link.provider)}
        <a href={link.url} class={["btn", classes]} target="_blank" rel="noopener noreferrer" title={link.provider}>
            <i class={["fa fa-brands", link.icon]}></i>
        </a>
    {/each}
</div>
