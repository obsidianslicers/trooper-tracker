<script lang="ts">
    let { notice }: { notice: string | null } = $props();
</script>

{#if notice}
    <div class="not-counted-notice d-flex align-items-baseline gap-2 mb-3" role="note">
        <i class="fa fa-fw fa-circle-info flex-shrink-0"></i>
        <span>{notice}</span>
    </div>
{/if}
