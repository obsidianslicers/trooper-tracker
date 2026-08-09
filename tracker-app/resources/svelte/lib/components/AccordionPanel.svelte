<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        label: string;
        children: Snippet;
    }

    let { label, children }: Props = $props();

    // Unique ID generator for Bootstrap data-bs-target
    const id = "card-" + crypto.randomUUID();

    // Reactive state to track collapse state for icon toggle
    let isExpanded = $state(false);

    function toggle() {
        isExpanded = !isExpanded;
    }
</script>

<div class="card mb-3">
    <div
        class={[
            "card-header d-flex justify-content-between align-items-center",
            isExpanded ? "" : "collapsed",
        ]}
        data-bs-toggle="collapse"
        data-bs-target={`#${id}`}
        role="button"
        aria-expanded={isExpanded}
        onclick={toggle}
        onkeydown={(e) =>
            e.key === "Enter" || e.key === " " ? toggle() : null}
        tabindex="0"
    >
        <span>{label}</span>
        <div class="d-flex align-items-center gap-2">
            <i
                class={[
                    "fa-solid collapse-icon",
                    isExpanded ? "fa-minus" : "fa-plus",
                ]}
            ></i>
        </div>
    </div>
    <div class="collapse" {id}>
        <div class="card-body">
            {@render children()}
        </div>
    </div>
</div>
