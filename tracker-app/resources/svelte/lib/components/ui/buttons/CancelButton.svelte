<script lang="ts">
    import { t } from "$lib/i18n";
    import Button from "./Button.svelte";

    interface Props {
        href?: string | null;
        label?: string | null;
        click?: (() => void) | null;
    }
    let { href = null, click = null, label = t("Cancel") }: Props = $props();
</script>

<Button btnclass="btn-secondary ms-2" {href} {click} {label} />
