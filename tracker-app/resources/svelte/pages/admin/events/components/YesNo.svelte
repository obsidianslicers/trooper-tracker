<script lang="ts">
    import { t } from "$lib/i18n";
    import InputSelect from "$lib/components/form/InputSelect.svelte";

    interface Props {
        label: string;
        value: boolean;
    }
    let { label, value = $bindable() }: Props = $props();

    const options = [
        { value: true, label: t("Yes") },
        { value: false, label: t("No") },
    ];
</script>

<InputSelect {label} {options} bind:value />
