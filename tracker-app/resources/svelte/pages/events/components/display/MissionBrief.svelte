<!--
    The mission brief. One of up to five lines (and a paragraph break) shows in full with nothing
    to press; a longer one shows three lines until it's decrypted. Measured in the browser, since
    the line count depends on the screen width. Where the event asks for it, the trooper confirms
    they've read it before Sign Up appears.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { router } from "@inertiajs/svelte";
    import { onMount } from "svelte";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";

    let { brief }: { brief: NonNullable<EventDetails["mission_brief"]> } = $props();

    let content: HTMLDivElement | undefined = $state();
    let opened = $state(false);
    let fits = $state(false);
    let full_height = $state(0);
    let sending = $state(false);

    const expanded = $derived(fits || opened);

    function measure(): void {
        if (!content) {
            return;
        }

        const style = getComputedStyle(content);
        const font = parseFloat(style.fontSize) || 16;
        const line = parseFloat(style.lineHeight) || font * 1.5;

        fits = content.scrollHeight <= line * 5 + font;
        full_height = content.scrollHeight + 40;
    }

    function toggle(): void {
        if (!fits) {
            opened = !opened;
            measure();
        }
    }

    function acknowledge(): void {
        router.post(brief.acknowledge_url, {}, {
            preserveScroll: true,
            preserveState: true,
            only: ["event", "shifts", "my_sign_ups", "tour", "flash", "toast"],
            onStart: () => (sending = true),
            onFinish: () => (sending = false),
        });
    }

    onMount(() => {
        measure();

        let timer: ReturnType<typeof setTimeout> | undefined;
        const resized = () => {
            clearTimeout(timer);
            timer = setTimeout(measure, 200);
        };

        window.addEventListener("resize", resized);

        return () => {
            clearTimeout(timer);
            window.removeEventListener("resize", resized);
        };
    });
</script>

<div class="mb-3">
    <SectionTitle>{t("Mission Brief")}</SectionTitle>

    <!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
    <div
        class={[
            "mission-brief-container position-relative overflow-hidden rounded",
            !expanded && "scanlines-overlay border border-success border-opacity-25",
            !fits && "pointer",
        ]}
        style:max-height={expanded ? `${full_height}px` : "120px"}
        onclick={toggle}
    >
        <div bind:this={content} class={[!expanded && "terminal-green"]}>
            {@html brief.html}
        </div>

        {#if !expanded}
            <div
                class="position-absolute bottom-0 start-0 w-100"
                style="height: 40px; background: linear-gradient(transparent, #1a1a1a); z-index: 3; pointer-events: none;"
            ></div>
        {/if}
    </div>

    {#if !fits}
        <br />
        <button
            type="button"
            class="btn btn-link btn-sm p-0 mt-1 text-decoration-none shadow-none fw-bold text-success text-uppercase"
            aria-expanded={expanded}
            onclick={toggle}
        >
            <i class={["fas ms-1 me-1", expanded ? "fa-terminal" : "fa-unlock-alt"]}></i>
            {expanded ? t("Close secure feed") : t("Decrypt mission brief")}
        </button>
    {/if}

    {#if brief.is_required}
        <div class="mt-3">
            {#if brief.is_acknowledged}
                <div class="alert alert-success text-start border border-success rounded-2 py-3 px-4 my-2">
                    <i class="fa fa-fw fa-exclamation-circle"></i>
                    {t("You have acknowledged this mission brief. You may sign up for available shifts below.")}
                </div>
            {:else}
                <div class="alert alert-warning text-start border border-warning rounded-2 py-3 px-4 my-2">
                    <i class="fa fa-fw fa-exclamation-circle"></i>
                    {t("You must review and acknowledge the mission brief before you can sign up for this deployment.")}
                    {#if !fits}
                        <span>{t("(Click 'Decrypt mission brief')")}</span>
                    {/if}
                </div>

                {#if expanded}
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-success" disabled={sending} onclick={acknowledge}>
                            <i class="fa fa-fw fa-check me-1"></i>
                            {t("I have read and understand the mission brief")}
                        </button>
                    </div>
                {/if}
            {/if}
        </div>
    {/if}
</div>
