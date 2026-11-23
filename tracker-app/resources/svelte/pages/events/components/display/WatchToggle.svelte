<script lang="ts">
    import { t } from "$lib/i18n";
    import { router } from "@inertiajs/svelte";

    let { is_watching, url }: { is_watching: boolean; url: string } = $props();

    let sending = $state(false);

    const toggle = () => {
        router.post(url, {}, {
            preserveScroll: true,
            preserveState: true,
            only: ["is_watching", "flash"],
            onStart: () => (sending = true),
            onFinish: () => (sending = false),
        });
    };
</script>

<div class="btn-group">
    <button
        type="button"
        class={["btn", is_watching ? "btn-secondary" : "btn-outline-secondary"]}
        title={is_watching ? t("Watching") : t("Watch Event")}
        aria-label={is_watching ? t("Watching") : t("Watch Event")}
        aria-pressed={is_watching}
        disabled={sending}
        onclick={toggle}
    >
        <i class={["fa fa-fw", is_watching ? "fa-bell" : "fa-bell-slash"]}></i>
    </button>
</div>
