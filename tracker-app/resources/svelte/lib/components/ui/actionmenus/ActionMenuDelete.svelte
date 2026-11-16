<script lang="ts">
    import { t } from "$lib/i18n";
    import ActionMenuLink from "./ActionMenuLink.svelte";
    interface Props {
        href?: string | null;
        click?: (() => void) | null;
    }

    let { href = null, click = null }: Props = $props();
</script>

<ActionMenuLink label={t("Delete")} icon="fa-times" {href} {click} />
