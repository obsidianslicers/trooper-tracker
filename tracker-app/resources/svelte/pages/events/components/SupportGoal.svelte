<script lang="ts">
    import { onMount } from "svelte";
    import { t } from "$lib/i18n";
    import type { SupportGoal } from "../models";
    import { SupportGoalViewModel } from "../models/vms/SupportGoalViewModel.svelte";

    let { support }: { support: SupportGoal } = $props();

    const vm = $derived(new SupportGoalViewModel(support));
    const COLORS = ["#f0c75e", "#198754", "#0d6efd", "#dc3545", "#ffffff", "#20c997"];

    let pieces: { left: number; delay: number; spin: number; color: string }[] = $state([]);

    onMount(() => {
        if (!vm.celebrate()) {
            return;
        }

        pieces = Array.from({ length: 60 }, (_, i) => ({
            left: Math.random() * 100,
            delay: Math.random() * 0.6,
            spin: Math.random() * 720 - 360,
            color: COLORS[i % COLORS.length],
        }));

        const timer = setTimeout(() => (pieces = []), 3500);

        return () => clearTimeout(timer);
    });
</script>

<div class="card mb-3 support-goal" class:support-goal-reached={vm.is_reached}>
    {#if pieces.length > 0}
        <div class="confetti" aria-hidden="true" data-testid="support-goal-confetti">
            {#each pieces as piece, i (i)}
                <i
                    style:left="{piece.left}%"
                    style:animation-delay="{piece.delay}s"
                    style:background={piece.color}
                    style:--spin="{piece.spin}deg"
                ></i>
            {/each}
        </div>
    {/if}
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h5 class="mb-0">
                {#if vm.is_reached}
                    🏆 <span class="reached-title">{t("Goal reached!")}</span>
                {:else}
                    🎯 {t("This Month's Support Goal")}
                {/if}
                <span class="text-muted">- ${vm.goal}</span>
            </h5>
            {#if support.url}
                <a href={support.url} target="_blank" rel="noopener" class="btn btn-sm btn-outline-success col-12 col-sm-auto">
                    {vm.is_reached ? t("❤️ Keep the momentum") : t("❤️ Support the Garrison")}
                    <i class="fa fa-fw fa-external-link"></i>
                </a>
            {/if}
        </div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <div
                class="progress flex-grow-1"
                role="progressbar"
                aria-valuenow={vm.bar_width}
                aria-valuetext="{vm.percent}%"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label={t("This Month's Support Goal")}
            >
                <div class="progress-bar bg-success" style:width="{vm.bar_width}%">
                    {vm.is_reached ? t(":amount raised", { amount: `$${vm.raised}` }) : `${vm.percent}%`}
                </div>
            </div>
            {#if vm.is_reached}
                <span class="reached-percent">{vm.percent}%</span>
            {/if}
        </div>
        {#if vm.is_reached}
            <p class="mb-0 small">
                {t("Thank you, troopers! We reached this month's goal. Anything more goes toward next month's costs.")}
            </p>
        {:else}
            <p class="mb-0 small text-muted d-none d-md-block">{@html support.message}</p>
        {/if}
    </div>
</div>

<style>
    .support-goal {
        position: relative;
        overflow: hidden;
    }

    .progress {
        height: 1.25rem;
    }

    .support-goal-reached {
        border-color: #d4a72c;
        box-shadow: 0 0 0 1px rgb(212 167 44 / 35%), 0 0 24px rgb(212 167 44 / 20%);
    }

    .support-goal-reached .progress-bar {
        background: linear-gradient(90deg, var(--bs-success), #d4a72c) !important;
        color: #000;
        font-weight: 700;
    }

    .reached-title,
    .reached-percent {
        color: #f0c75e;
    }

    .reached-percent {
        font-weight: 700;
    }

    .confetti {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 1;
    }

    .confetti i {
        position: absolute;
        top: -1rem;
        width: 0.5rem;
        height: 0.8rem;
        border-radius: 2px;
        animation: confetti-fall 2.5s ease-in forwards;
    }

    @keyframes confetti-fall {
        to {
            transform: translateY(14rem) rotate(var(--spin));
            opacity: 0;
        }
    }
</style>
