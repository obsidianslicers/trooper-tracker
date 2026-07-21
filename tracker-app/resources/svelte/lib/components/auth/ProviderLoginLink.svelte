<script lang="ts">
    import { ProviderLoginViewModel } from '$lib/domains/auth';
    import { untrack } from 'svelte';

    interface Props {
        label: string;
        show?: boolean;
        url?: string | null;
    }

    let { label, show = false, url = null }: Props = $props();

    let vm = untrack(() => new ProviderLoginViewModel(label, show, url));
</script>

{#if vm.show}
    <button
        type="button"
        class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2 mb-2 w-100"
        onclick={vm.handleClick}
        disabled={vm.submitting}
    >
        {#if vm.submitting}
            <i class="fa-solid fa-spinner fa-spin me-2"></i>
            Logging in ...
        {:else}
            <span>{vm.label}</span>
        {/if}
    </button>
{/if}
