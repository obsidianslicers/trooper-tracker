<script lang="ts">
    import type { ForumGroup } from "../../models/vms/PendingPermissionRequestViewModel.svelte";

    interface Props {
        group: ForumGroup;
    }

    let { group }: Props = $props();
</script>

<span class="d-inline-flex align-items-center gap-2">
    {#if group.banner_image_url}
        <img class="forum-group-badge" src={group.banner_image_url} alt="" loading="lazy" />
    {/if}
    <span>{group.title}</span>
</span>
