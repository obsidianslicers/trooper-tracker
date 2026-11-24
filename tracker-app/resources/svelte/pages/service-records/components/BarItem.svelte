<!--
    One labelled bar: a name, its count and how it compares to the biggest.
-->
<script lang="ts">
    interface Props {
        label: string;
        count: number;
        unit: string;
        max: number;
    }
    let { label, count, unit, max }: Props = $props();
</script>

<div class="d-flex justify-content-between align-items-center mb-1">
    <span class="fw-bold small text-uppercase">{label}</span>
    <span class="text-primary fw-bold">
        {count}
        <small class="text-muted unit">{unit}</small>
    </span>
</div>
<div class="progress bar">
    <div
        class="progress-bar bg-success"
        role="progressbar"
        style:width="{(count / Math.max(max, 1)) * 100}%"
    ></div>
</div>

<style>
    .unit {
        font-size: 0.6rem;
    }
    .bar {
        height: 6px;
    }
</style>
