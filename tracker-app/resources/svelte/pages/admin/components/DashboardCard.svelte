<!--
    One tool on the Command Staff dashboard; the whole card opens it.
-->
<script lang="ts">
    import type { CardLine } from "../models/vms/DashboardViewModel.svelte";

    interface Props {
        label: string;
        icon: string;
        href: string;
        lines: CardLine[];
        danger?: boolean;
    }
    let { label, icon, href, lines, danger = false }: Props = $props();
</script>

<div class="col">
    <div class={["card h-100 position-relative", danger && "border-danger"]}>
        <div class="card-header">
            <i class="text-white fa fa-fw {icon} me-2"></i>
            <a {href} class="stretched-link text-reset text-decoration-none">{label}</a>
        </div>
        <div class="card-body">
            {#each lines as line, i (i)}
                <p
                    class={[
                        "card-text",
                        i === lines.length - 1 && "mb-0",
                        line.tone && `text-${line.tone}`,
                        line.tone === "danger" && "fw-bold",
                    ]}
                >
                    {#if line.tone === "danger"}
                        <i class="fa fa-fw fa-solid fa-circle-exclamation"></i>
                    {/if}
                    {line.text}
                </p>
            {/each}
        </div>
    </div>
</div>
