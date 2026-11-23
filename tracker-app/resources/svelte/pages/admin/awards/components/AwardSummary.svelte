<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import type { AwardSummary } from "../models";

    interface Props {
        award: AwardSummary;
        with_frequency?: boolean;
    }

    let { award, with_frequency = true }: Props = $props();
</script>

<InputContainer>
    <InputText label={t("Award")} value={award.name} disabled={true} />
</InputContainer>
<InputContainer>
    <InputText label={t("Organization")} value={award.organization_name} disabled={true} />
</InputContainer>
{#if with_frequency}
    <InputContainer>
        <InputText label={t("Frequency")} value={award.frequency_label} disabled={true} />
    </InputContainer>
{/if}
