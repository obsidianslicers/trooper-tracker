<!--
    One number on the operations dashboard, with what it counts on hover.
-->
<script lang="ts">
    import { tooltip } from "$lib/actions/tooltip";

    interface Props {
        label: string;
        help: string;
        icon: string;
        value: string;
        note?: string | null;
        /** Bootstrap text colour for the number, e.g. "text-primary". */
        tone?: string;
        /** A coloured edge on the left, e.g. "border-primary". */
        accent?: string | null;
        danger?: boolean;
    }

    let {
        label,
        help,
        icon,
        value,
        note = null,
        tone = "",
        accent = null,
        danger = false,
    }: Props = $props();
</script>

<div class="col-md-3">
    <div class={["card border-0 shadow-sm h-100", accent && `border-start ${accent} border-4`]}>
        <div class="card-body">
            <h6
                class={["text-uppercase small fw-bold", danger ? "text-danger" : "text-muted"]}
                use:tooltip={help}
            >
                <i class="fa-fw fa-solid {icon} me-1"></i>
                {label}
            </h6>
            <h2 class="display-6 fw-bold {tone}">{value}</h2>
            {#if note}
                <small class="text-muted">{note}</small>
            {/if}
        </div>
    </div>
</div>
