<script lang="ts">
    import { fade } from 'svelte/transition';
    import Alert from '../components/ui/Alert.svelte';
    import type { FlashType } from '../states/flash-state.svelte';
    import flashState from '../states/flash-state.svelte';

    function icon_for(type: FlashType): string {
        if (type === 'success') return 'fa-circle-check';
        if (type === 'warning') return 'fa-circle-exclamation';
        if (type === 'danger') return 'fa-circle-xmark';
        return 'fa-circle-info';
    }
</script>

{#if flashState.all.length > 0}
    <div id="flash-messages">
        {#each flashState.all as message (message.id)}
            <div out:fade={{ duration: 180 }}>
                <Alert
                    type={message.type}
                    icon={icon_for(message.type)}
                    classes="mt-2 d-flex align-items-center gap-2"
                >
                    <strong class="flex-grow-1">{message.text}</strong>

                    {#if message.allow_dismiss}
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="Dismiss"
                            onclick={() => flashState.dismiss(message.id)}
                        ></button>
                    {/if}
                </Alert>
            </div>
        {/each}
    </div>
{/if}
