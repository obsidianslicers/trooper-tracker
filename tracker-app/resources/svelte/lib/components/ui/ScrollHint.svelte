<!--
    A row that scrolls sideways on narrow screens (tab bars, the calendar's months). A chevron
    over a fade shows on whichever side has more, and tapping it scrolls that way. The active or
    tapped item scrolls fully into view. The chevrons are hidden from keyboards and screen readers,
    which reach every item already.
-->
<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { ClassValue } from 'svelte/elements';
    import { ScrollHintViewModel } from '../models/ScrollHintViewModel.svelte';

    interface Props {
        children: Snippet;
        class?: ClassValue;
    }

    let { children, class: classes }: Props = $props();

    const vm = new ScrollHintViewModel();
    let row: HTMLDivElement;

    $effect(() => {
        const update = () => vm.update(row);
        const observer = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(update);

        observer?.observe(row);
        if (row.firstElementChild) {
            observer?.observe(row.firstElementChild);
        }

        // The tabs pick their active tab after mounting, so wait a frame before revealing it.
        const frame = requestAnimationFrame(() => {
            const active = row.querySelector<HTMLElement>('.active, [aria-current="page"]');
            if (active) {
                vm.reveal(row, active, 'instant');
            }
            update();
        });

        return () => {
            cancelAnimationFrame(frame);
            observer?.disconnect();
        };
    });

    function revealTapped(event: MouseEvent): void {
        const item = (event.target as HTMLElement).closest<HTMLElement>('li, a, button');
        if (item && row.contains(item)) {
            vm.reveal(row, item);
        }
    }
</script>

<div class="scroll-hint">
    <!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
    <div
        bind:this={row}
        class={[
            'scroll-hint-row',
            { 'fade-before': vm.more_before, 'fade-after': vm.more_after },
            classes,
        ]}
        onscroll={() => vm.update(row)}
        onclick={revealTapped}
    >
        {@render children()}
    </div>
    {#if vm.more_before}
        <button
            type="button"
            class="btn btn-link scroll-hint-chevron before"
            tabindex="-1"
            aria-hidden="true"
            onclick={() => vm.step(row, -1)}
        >
            <i class="fa fa-chevron-left"></i>
        </button>
    {/if}
    {#if vm.more_after}
        <button
            type="button"
            class="btn btn-link scroll-hint-chevron after"
            tabindex="-1"
            aria-hidden="true"
            onclick={() => vm.step(row, 1)}
        >
            <i class="fa fa-chevron-right"></i>
        </button>
    {/if}
</div>

<style>
    .scroll-hint {
        position: relative;
    }

    .scroll-hint-row {
        --clear-before: 0px;
        --clear-after: 0px;
        --fade-before: 0px;
        --fade-after: 0px;
        overflow-x: auto;
        scrollbar-width: none;
    }

    /* Only while something is hidden: a mask clips whatever hangs outside the row. */
    .scroll-hint-row.fade-before,
    .scroll-hint-row.fade-after {
        mask-image: linear-gradient(
            to right,
            transparent var(--clear-before),
            #000 var(--fade-before),
            #000 calc(100% - var(--fade-after)),
            transparent calc(100% - var(--clear-after))
        );
    }

    .scroll-hint-row::-webkit-scrollbar {
        display: none;
    }

    /* Clear under the chevron, then fading into the tabs. */
    .scroll-hint-row.fade-before {
        --clear-before: 1.75rem;
        --fade-before: 4.5rem;
    }

    .scroll-hint-row.fade-after {
        --clear-after: 1.75rem;
        --fade-after: 4.5rem;
    }

    .scroll-hint-chevron {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2.5rem;
        display: flex;
        align-items: center;
        border: 0;
        color: var(--bs-body-color);
        text-decoration: none;
        z-index: 2;
    }

    .scroll-hint-chevron.before {
        left: 0;
        justify-content: flex-start;
    }

    .scroll-hint-chevron.after {
        right: 0;
        justify-content: flex-end;
    }
</style>
