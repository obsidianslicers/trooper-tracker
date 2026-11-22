<!--
    The "How sign-up works" tour: where each step of signing up happens. Who it starts by itself
    for is the tracker.signup_tour setting (App\Support\SignUpTour); it then stays quiet on that
    browser once seen. The button above the roster replays it. The example is a picture only:
    nothing in it works.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { onMount, tick, untrack } from "svelte";
    import type { SignUpTourData, SignUpTourViewModel } from "../../models";
    import SignUpStatus from "./SignUpStatus.svelte";

    let { tour, viewer_name, vm }: { tour: SignUpTourData; viewer_name: string; vm: SignUpTourViewModel } = $props();

    let next_button: HTMLButtonElement | undefined = $state();

    const going = $derived({ value: "going", label: tour.going_label, color: "text-success" });

    /** The dimmed page goes straight under <body>, so it's placed in page coordinates. */
    function toBody(node: HTMLElement) {
        document.body.appendChild(node);

        return { destroy: () => node.remove() };
    }

    $effect(() => {
        if (vm.active && vm.index >= 0) {
            void tick().then(() => next_button?.focus());
        }
    });

    onMount(() => {
        const timer = vm.autoStart(untrack(() => tour.auto_start));

        return () => {
            if (timer !== null) {
                clearTimeout(timer);
            }
        };
    });

</script>

<svelte:window onkeydown={(e) => e.key === "Escape" && vm.active && vm.end()} />

{#if vm.active}
    <!-- the example: your row in a shift's list, its Edit area open -->
    <div class="card mb-3 signup-tour-example" inert>
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <b>{t("Example shift")}</b>
            <span class="badge text-bg-secondary">{t("Example only, not a real sign-up")}</span>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">{t("After you press Sign Up and Confirm:")}</div>
            <div class="roster mb-3">
                <div class="roster-row roster-row--mine" data-tour-example="panel">
                    <div class="roster-row__main">
                        <span class="roster-row__number text-muted">1</span>
                        <div class="roster-row__who">
                            <a href="#top">{viewer_name}</a>
                            <span class="badge text-bg-secondary ms-1">{t("You")}</span>
                        </div>
                        <div class="roster-row__costume">
                            {#if tour.requires_costume}<b>{t("Your costume")}</b>{/if}
                        </div>
                        <div class="roster-row__status"><SignUpStatus status={going} /></div>
                        <div class="roster-row__edit">
                            <span class="btn btn-sm btn-outline-secondary btn-secondary-action">{t("Done")}</span>
                        </div>
                    </div>
                    <div class="roster-row__editor">
                        <div class="row g-3">
                            <div class="col-12 col-md-7">
                                {#if tour.requires_costume}
                                    <span class="form-label small text-muted mb-1 d-block">{t("Costume")}</span>
                                    <select class="form-select form-select-sm" aria-label={t("Costume")}>
                                        <option>{t("Your costume")}</option>
                                    </select>
                                    <span class="btn btn-sm btn-outline-secondary btn-secondary-action mt-2"
                                          data-tour-example="backup">{t("Add a backup costume")}</span>
                                {/if}
                            </div>
                            <div class="col-12 col-md-5 align-self-start text-md-end">
                                <div class="d-flex flex-wrap gap-3 align-items-baseline justify-content-md-end"
                                     data-tour-example="plans">
                                    {#if tour.has_plans}
                                        <select class="form-select form-select-sm w-auto" aria-label={t("Your plans")}>
                                            {#each tour.plans as plan (plan)}
                                                <option selected={plan === tour.going_label}>{plan}</option>
                                            {/each}
                                        </select>
                                    {/if}
                                    <span class="btn btn-link btn-sm p-0 text-danger">{t("Cancel my sign-up")}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="shift-links__row mb-3" data-tour-example="extras">
                <span class="btn btn-sm btn-outline-secondary btn-secondary-action">{t("Add a friend")}</span>
                <span class="btn btn-sm btn-outline-secondary btn-secondary-action">{t("Add a guest")}</span>
            </div>

            <div class="small text-muted mb-2">{t("After the troop:")}</div>
            <div class="row" data-tour-example="after">
                <div class="col-12 col-md-4"><a href="#top">{viewer_name}</a></div>
                <div class="col-12 col-md-8 mt-2 mt-md-0 text-md-end">
                    <div class="input-group input-group-sm d-inline-flex w-auto">
                        <span class="btn btn-outline-success">{t("Made It")}</span>
                        <span class="btn btn-outline-danger">{t("Missed It")}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div use:toBody class={["signup-tour-spotlight", !vm.spot && "signup-tour-spotlight--none"]}
         style={vm.spot_style}></div>

    <div class="card shadow-lg signup-tour-card" role="dialog" aria-modal="false" aria-labelledby="signup-tour-title">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h6 id="signup-tour-title" class="mb-0 fw-bold">{vm.step?.title ?? ""}</h6>
                <button type="button" class="btn-close" aria-label={t("Close the tour")} onclick={vm.end}></button>
            </div>
            <div class="mb-3">{@html vm.step?.body ?? ""}</div>
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="small text-muted">{vm.counter}</span>
                <div class="d-flex gap-2">
                    {#if !vm.is_last}
                        <button type="button" class="btn btn-sm btn-link text-muted" onclick={vm.end}>{t("Skip tour")}</button>
                    {/if}
                    {#if vm.index > 0}
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick={vm.back}>{t("Back")}</button>
                    {/if}
                    <button type="button" class="btn btn-sm btn-primary" bind:this={next_button} onclick={vm.next}>
                        {vm.is_last ? t("Done") : t("Next")}
                    </button>
                </div>
            </div>
        </div>
    </div>
{/if}
